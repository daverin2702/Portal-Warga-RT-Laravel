<?php

namespace App\Http\Controllers\Letters;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterRequest;
use App\Services\LetterWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ApprovalController extends Controller
{
    public function __construct(private LetterWorkflow $workflow) {}

    public function index(): View
    {
        return view('letters.approvals', [
            'requests' => LetterRequest::query()->with('household')->whereIn('status', array_keys(LetterWorkflow::REQUEST_STEPS))->oldest()->get(),
            'drafts' => Letter::query()->with(['household', 'creator'])->whereIn('status', array_keys(LetterWorkflow::DRAFT_STEPS))->oldest()->get(),
        ]);
    }

    public function request(Request $request, LetterRequest $letterRequest): RedirectResponse
    {
        $data = $this->validated($request);

        return $this->run(fn () => $this->workflow->advanceRequest($letterRequest, $request->user(), $data['action'], $data['note'] ?? '', ($data['signature'] ?? 'qr') === 'qr'));
    }

    public function draft(Request $request, Letter $letter): RedirectResponse
    {
        $data = $this->validated($request);

        return $this->run(fn () => $this->workflow->advanceDraft($letter, $request->user(), $data['action'], $data['note'] ?? '', ($data['signature'] ?? ($letter->qr_signature ? 'qr' : 'basah')) === 'qr'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'action' => ['required', Rule::in(['teruskan', 'periksa', 'setujui', 'tolak'])],
            'note' => ['nullable', 'string', 'max:250'],
            'signature' => ['nullable', Rule::in(['qr', 'basah'])],
        ]);
    }

    private function run(callable $fn): RedirectResponse
    {
        try {
            return back()->with('success', $fn());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
