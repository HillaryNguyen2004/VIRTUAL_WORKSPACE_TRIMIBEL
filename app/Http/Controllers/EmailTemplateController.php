<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::all();
        return view('tasks.email-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('tasks.email-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'subject' => 'required',
            'content' => 'required',
        ]);

        EmailTemplate::create($request->all());

        return redirect()->route('email-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        // return view('tasks.email-templates.edit', compact('emailTemplate'));
        return view('tasks.email-templates.create', compact('emailTemplate'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $request->validate([
            'name' => 'required',
            'subject' => 'required',
            'content' => 'required',
        ]);

        $emailTemplate->update($request->all());

        return redirect()->route('email-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->route('email-templates.index')->with('success', 'Template deleted.');
    }

    public function sendTemplateEmail($templateId, $recipientEmail, $data)
    {
        $template = EmailTemplate::findOrFail($templateId);

        // Replace shortcodes like {first_name} with real data
        $content = strtr($template->content, $data);
        $subject = strtr($template->subject, $data);

        \Mail::send([], [], function ($message) use ($recipientEmail, $subject, $content) {
            $message->to($recipientEmail)
                    ->subject($subject)
                    ->setBody($content, 'text/html');
        });
    }

}
