<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdStore extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'texte' => ['required', 'string', 'max:1000'],
            'pseudo' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
        ];
    }
}