<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'client_id' => 'required',
            'client_secret' => 'required',
            'name' => 'required',
            'password' => 'required|min:8|max:20',
            'c_password' => 'required|min:8|max:20|same:password',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute là trường bắt buộc',
            'email' => ':attribute không hợp lệ',
            'unique' => ':attribute đã tồn tại',
            'min' => ':attribute tối thiểu :min kí tự',
            'max' => ':attribute tối đa :max kí tự',
            'same' => ':attribute không trùng khớp',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'Email',
            'client_id' => 'Client Id',
            'client_secret' => 'Client Serces',
            'password' => 'Mật khẩu',
            'c_password' => 'Mật khẩu',
            'name' => 'Họ và tên',
        ];
    }
}
