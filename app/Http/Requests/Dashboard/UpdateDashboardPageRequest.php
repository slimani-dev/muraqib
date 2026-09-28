<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\TeamPermission;
use App\Rules\DashboardLayout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->user()->currentTeam;

        return $team !== null && $this->user()->hasTeamPermission($team, TeamPermission::UpdateDashboard);
    }

    /**
     * `layout: null` resets the default page to the built-in layout (other pages to an empty one).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:40'],
            'layout' => ['sometimes', 'nullable', 'array', new DashboardLayout],
        ];
    }
}
