<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');
        $targetUserId = $targetUser instanceof User ? $targetUser->id : (int) $targetUser;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($targetUserId)],
            'password' => ['nullable', 'string', 'min:8'],
            'auth_method' => ['nullable', 'string', Rule::in(array_keys(User::AUTH_METHODS))],
            'roles' => ['required', 'array'],
            'roles.*' => [Rule::exists('roles', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ];
    }
}
