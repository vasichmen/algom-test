<?php


namespace App\Http\Controllers;

use App\Http\Requests\CheckLinkRequest;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function checkLink(CheckLinkRequest $request): JsonResponse
    {
        $link = $request->validated()['link'];
        $code = $this->sendRequest($link);

        return response()->json(compact('code'));
    }

    private function sendRequest(string $link): int
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code;
    }
}