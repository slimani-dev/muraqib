<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\TeamPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDashboardPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->user()->currentTeam;

        return $team !== null && $this->user()->hasTeamPermission($team, TeamPermission::UpdateDashboard);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
        ];
    }
}
