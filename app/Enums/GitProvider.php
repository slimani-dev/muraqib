<?php

namespace App\Enums;

use App\Services\Git\BitbucketClient;
use App\Services\Git\GitClient;
use App\Services\Git\GiteaClient;
use App\Services\Git\GitHubClient;
use App\Services\Git\GitLabClient;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum GitProvider: string implements HasIcon, HasLabel
{
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Gitea = 'gitea';
    case Forgejo = 'forgejo';
    case Codeberg = 'codeberg';
    case Bitbucket = 'bitbucket';

    public function getLabel(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::GitLab => 'GitLab',
            self::Gitea => 'Gitea',
            self::Forgejo => 'Forgejo',
            self::Codeberg => 'Codeberg',
            self::Bitbucket => 'Bitbucket',
        };
    }

    /** Blade icon, for Filament. */
    public function getIcon(): string
    {
        return "si-{$this->value}";
    }

    /** Iconify icon, for the dashboard (the fallback logo of a repo without one). */
    public function iconifyIcon(): string
    {
        return "simple-icons:{$this->value}";
    }

    /**
     * The web address of the hosted service, or null for self-hosted-only software.
     */
    public function defaultBaseUrl(): ?string
    {
        return match ($this) {
            self::GitHub => 'https://github.com',
            self::GitLab => 'https://gitlab.com',
            self::Codeberg => 'https://codeberg.org',
            self::Bitbucket => 'https://bitbucket.org',
            self::Gitea, self::Forgejo => null,
        };
    }

    public function requiresBaseUrl(): bool
    {
        return $this->defaultBaseUrl() === null;
    }

    /**
     * @return class-string<GitClient>
     */
    public function clientClass(): string
    {
        return match ($this) {
            self::GitHub => GitHubClient::class,
            self::GitLab => GitLabClient::class,
            self::Gitea, self::Forgejo, self::Codeberg => GiteaClient::class,
            self::Bitbucket => BitbucketClient::class,
        };
    }
}
