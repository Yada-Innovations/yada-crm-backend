<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return response()->json(EmailTemplate::where('active', true)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:email_templates,name',
            'subject' => 'required|string',
            'body' => 'required|string',
            'category' => 'nullable|string',
            'variables' => 'nullable|array',
        ]);

        $data['id'] = Str::uuid();
        $data['created_by'] = $request->user()->id;
        $template = EmailTemplate::create($data);
        return response()->json($template, 201);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return response()->json($emailTemplate);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'subject' => 'sometimes|string',
            'body' => 'sometimes|string',
            'active' => 'sometimes|boolean',
        ]);
        $emailTemplate->update($data);
        return response()->json($emailTemplate);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return response()->json(['message' => 'Template deleted']);
    }
}