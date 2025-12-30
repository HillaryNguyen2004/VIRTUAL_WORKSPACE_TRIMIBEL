<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailTemplateRequest;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\RedirectResponse;
use App\Repositories\EmailTemplateRepository;
use Illuminate\View\View;
use Illuminate\Http\Request;


class EmailTemplateController extends Controller
{
    protected EmailTemplateService $emailService;
    protected EmailTemplateRepository $templateRepo;

    public function __construct(EmailTemplateService $emailService, EmailTemplateRepository $templateRepo)
    {
        $this->emailService = $emailService;
        $this->templateRepo = $templateRepo;
    }

    public function index(Request $request)
    {
        $filters = [
        'search' => $request->input('search'),
        'sort_by' => $request->input('sort_by'), // name or created_at
        'sort_dir' => $request->input('sort_dir'), // asc or desc
        ];

        $templates = $this->templateRepo->getFilteredPaginated($filters, 10);
        return view('tasks.email-templates.index', compact('templates', 'filters'));
    }

    public function create(): View
    {
        return view('tasks.email-templates.create');
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        $this->templateRepo->create($request->validated());
        return redirect()->route('email-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('tasks.email-templates.create', compact('emailTemplate'));
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->templateRepo->update($emailTemplate, $request->validated());
        return redirect()->route('email-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->templateRepo->delete($emailTemplate);
        return redirect()->route('email-templates.index')->with('success', 'Template deleted.');
    }

    public function send($templateId, $recipientEmail, array $data): void
    {
        $this->emailService->sendTemplateEmail($templateId, $recipientEmail, $data);
    }
}
