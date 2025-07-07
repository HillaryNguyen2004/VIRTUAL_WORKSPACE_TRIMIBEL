<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailTemplateRequest;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index(): View
    {
        $templates = EmailTemplate::latest()->paginate(3);
        return view('tasks.email-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('tasks.email-templates.create');
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        EmailTemplate::create($request->validated());
        return redirect()->route('email-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('tasks.email-templates.create', compact('emailTemplate'));
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($request->validated());
        return redirect()->route('email-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->delete();
        return redirect()->route('email-templates.index')->with('success', 'Template deleted.');
    }

    public function send($templateId, $recipientEmail, array $data): void
    {
        $this->emailService->sendTemplateEmail($templateId, $recipientEmail, $data);
    }
}
