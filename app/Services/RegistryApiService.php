<?php

namespace App\Services;

use App\Traits\HasApiMonitoring;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RegistryApiService
{
    use HasApiMonitoring;

    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.registry_api.url', 'http://localhost:8080'), '/');
    }

    /**
     * Get all tags for an image natively.
     */
    public function getTags(string $image): ?array
    {
        $imageName = $this->stripTag($image);

        // Cache the raw tag list for 15 minutes to avoid rate limits on bulk checks.
        return Cache::remember('registry_tags_'.md5($imageName), now()->addMinutes(15), function () use ($imageName) {
            try {
                $response = $this->http('getTags')->timeout(30)
                    ->get("{$this->baseUrl}/tags", [
                        'image' => $imageName,
                    ]);

                if ($response->successful()) {
                    return $response->json('tags');
                }

                $error = $response->json('error') ?? 'Unknown error';
                Log::warning("RegistryApiService::getTags failed for {$imageName}: {$error}");

                return null;
            } catch (\Exception $e) {
                Log::error("RegistryApiService::getTags exception for {$imageName}: {$e->getMessage()}");

                return null;
            }
        });
    }

    /**
     * Check if a tag looks like a semantic version (e.g., v2.5.6, 1.2.3, 2.5.6-beta1).
     */
    protected function isSemverLike(string $tag): bool
    {
        // Match: optional 'v' prefix, then digit(s).digit(s) with optional .patch and optional pre-release suffix
        return (bool) preg_match('/^v?\d+\.\d+(\.\d+)?([-+.].*)?$/i', $tag);
    }

    /**
     * Check if a tag is obvious noise (not a real release).
     */
    protected function isNoiseTag(string $tag): bool
    {
        $lower = strtolower($tag);

        // PR builds
        if (str_starts_with($lower, 'pr-')) {
            return true;
        }

        // SHA-based tags (7+ hex chars only, like commit hashes)
        if (preg_match('/^[0-9a-f]{7,}$/i', $tag)) {
            return true;
        }

        // sha256- prefixed digest tags
        if (str_starts_with($lower, 'sha256-')) {
            return true;
        }

        // Date-based tags (e.g., 20201215)
        if (preg_match('/^v?20\d{6}/i', $tag)) {
            return true;
        }

        // Unstable or dev tags
        if (str_contains($lower, 'unstable') || str_contains($lower, 'nightly') || str_contains($lower, 'dev')) {
            return true;
        }

        return false;
    }

    /**
     * Pick the latest stable tag (SemVer) out of a raw tag array.
     * Only considers tags that look like actual version numbers.
     */
    public function getLatestStableTag(array $tags): ?string
    {
        // Only keep tags that look like real semver versions
        $semverTags = collect($tags)
            ->filter(fn ($t) => $this->isSemverLike($t) && ! $this->isNoiseTag($t))
            ->values()
            ->all();

        if (empty($semverTags)) {
            // Fallback: try well-known aliases
            foreach (['latest', 'release', 'stable'] as $alias) {
                if (in_array($alias, $tags)) {
                    return $alias;
                }
            }

            return $tags[0] ?? null;
        }

        // Sort descending by semantic version
        usort($semverTags, function ($a, $b) {
            $vA = ltrim($a, 'v');
            $vB = ltrim($b, 'v');

            return version_compare($vB, $vA);
        });

        return $semverTags[0];
    }

    /**
     * Get a curated list of relevant tags (max 10) for display.
     * Includes well-known aliases + top semver versions.
     */
    public function getRelevantTags(array $tags, int $limit = 10): array
    {
        $wellKnownAliases = ['latest', 'release', 'stable', 'lts'];
        $result = [];

        // 1. Add well-known aliases that exist in the tag list
        foreach ($wellKnownAliases as $alias) {
            if (in_array($alias, $tags) && count($result) < $limit) {
                $result[] = $alias;
            }
        }

        // 2. Collect semver tags (not noise), sorted descending
        $semverTags = collect($tags)
            ->filter(fn ($t) => $this->isSemverLike($t) && ! $this->isNoiseTag($t))
            ->unique()
            ->values()
            ->all();

        usort($semverTags, function ($a, $b) {
            return version_compare(ltrim($b, 'v'), ltrim($a, 'v'));
        });

        // 3. Fill remaining slots with top semver tags
        foreach ($semverTags as $tag) {
            if (count($result) >= $limit) {
                break;
            }
            if (! in_array($tag, $result)) {
                $result[] = $tag;
            }
        }

        return $result;
    }

    /**
     * Get the exact digest for a specific image tag.
     */
    public function getDigestForTag(string $image, string $tag): ?string
    {
        $imageName = $this->stripTag($image);

        return Cache::remember('registry_digest_'.md5($imageName.':'.$tag), now()->addMinutes(15), function () use ($imageName, $tag) {
            try {
                $response = $this->http('getDigestForTag')->timeout(30)
                    ->get("{$this->baseUrl}/digest", [
                        'image' => $imageName,
                        'tag' => $tag,
                    ]);

                if ($response->successful()) {
                    return $response->json('digest');
                }

                $error = $response->json('error') ?? 'Unknown error';
                Log::warning("RegistryApiService::getDigestForTag failed for {$imageName}:{$tag}: {$error}");

                return null;
            } catch (\Exception $e) {
                Log::error("RegistryApiService::getDigestForTag exception for {$imageName}:{$tag}: {$e->getMessage()}");

                return null;
            }
        });
    }

    public function getManifestListDigests(string $image, string $tag): ?array
    {
        $imageName = $this->stripTag($image);

        return Cache::remember('registry_manifest_digests_'.md5($imageName.':'.$tag), now()->addMinutes(15), function () use ($imageName, $tag) {
            try {
                $response = $this->http('getManifest')->timeout(30)
                    ->get("{$this->baseUrl}/manifest", [
                        'image' => $imageName,
                        'tag' => $tag,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['manifests']) && is_array($data['manifests'])) {
                        $digests = [];
                        foreach ($data['manifests'] as $manifest) {
                            if (isset($manifest['digest'])) {
                                $digests[] = $manifest['digest'];
                            }
                        }

                        return $digests;
                    }
                }

                return null;
            } catch (\Exception $e) {
                Log::error("RegistryApiService::getManifest exception for {$imageName}:{$tag}: {$e->getMessage()}");

                return null;
            }
        });
    }

    /**
     * Full flow: checks the container's current digest against the best stable tag digest.
     *
     * @return array{is_latest: bool, latest_tag?: string, relevant_tags?: array, error?: string}|null
     */
    public function checkDigest(string $image, string $currentDigest): ?array
    {
        $tags = $this->getTags($image);

        if (! $tags) {
            return ['error' => 'Failed to fetch image tags from registry.'];
        }

        $latestStableTag = $this->getLatestStableTag($tags);

        // If the container tracks a specific alias (e.g. latest, nightly), we should check the digest of that tag.
        $currentTagFromImage = $this->getTagNameFromImage($image) ?? 'latest';
        $aliases = ['latest', 'stable', 'lts', 'nightly', 'unstable', 'develop'];

        if (in_array(strtolower($currentTagFromImage), $aliases)) {
            $targetTag = $currentTagFromImage;
        } else {
            $targetTag = $latestStableTag;
        }

        if (! $targetTag) {
            return ['error' => 'No tags found for image.'];
        }

        $latestDigest = $this->getDigestForTag($image, $targetTag);

        if (! $latestDigest) {
            return ['error' => "Failed to fetch digest for target tag: {$targetTag}"];
        }

        // Ensure both digests are prefixed appropriately for reliable comparison
        $currentStr = str_starts_with($currentDigest, 'sha256:') ? $currentDigest : "sha256:{$currentDigest}";
        $latestStr = str_starts_with($latestDigest, 'sha256:') ? $latestDigest : "sha256:{$latestDigest}";

        $isLatest = $currentStr === $latestStr;

        if (! $isLatest) {
            $manifestDigests = $this->getManifestListDigests($image, $targetTag);
            if (! empty($manifestDigests) && in_array($currentStr, $manifestDigests)) {
                $isLatest = true;
            }
        }

        return [
            'is_latest' => $isLatest,
            'current_tag' => $currentTagFromImage,
            'latest_tag' => $targetTag,
            'latest_digest' => $latestDigest,
            'relevant_tags' => $this->getRelevantTags($tags),
        ];
    }

    /**
     * Extract tag from image (e.g., "nginx:1.21.1" → "1.21.1").
     */
    protected function getTagNameFromImage(string $image): ?string
    {
        // Remove digest first if present
        $imageWithoutDigest = preg_replace('/@sha256:.*$/', '', $image);

        if (preg_match('/:(?P<tag>[^@:]+)$/', $imageWithoutDigest, $matches)) {
            return $matches['tag'] !== 'latest' ? $matches['tag'] : null;
        }

        return null;
    }

    /**
     * Strip the tag from an image reference (e.g., "nginx:latest" → "nginx").
     */
    protected function stripTag(string $image): string
    {
        // Strip everything from the first '@' (digest) or ':' (tag) onwards
        return preg_replace('/[:@].*$/', '', $image);
    }
}
