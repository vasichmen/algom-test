<?php


namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidatesWhenResolvedTrait;

class ReportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'link' => 'required',
        ];
    }



}