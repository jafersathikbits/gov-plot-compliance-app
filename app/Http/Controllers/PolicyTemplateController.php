<?php

namespace App\Http\Controllers;

use App\Models\PolicyTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PolicyTemplateController extends Controller
{
    public const MILESTONE_TYPES = ['investment', 'employment', 'construction', 'time_bound', 'generic'];

    public function index()
    {
        $templates = PolicyTemplate::orderBy('name')->get();
        return view('policies.index', compact('templates'));
    }

    public function create()
    {
        return view('policies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'warning_days' => ['required', 'integer', 'min:1', 'max:365'],
            'milestone_titles' => ['required', 'array', 'min:1'],
            'milestone_titles.*' => ['required', 'string', 'max:255'],
            'milestone_types' => ['required', 'array'],
            'milestone_types.*' => ['required', Rule::in(self::MILESTONE_TYPES)],
            'milestone_due_months' => ['required', 'array'],
            'milestone_due_months.*' => ['required', 'integer', 'min:0', 'max:600'],
            'milestone_required_values' => ['required', 'array'],
            'milestone_required_values.*' => ['required', 'numeric', 'min:0'],
            'milestone_units' => ['required', 'array'],
            'milestone_units.*' => ['required', 'string', 'max:50'],
        ]);

        if (PolicyTemplate::where('code', $data['code'])->exists()) {
            return back()->withInput()->withErrors(['code' => 'A policy template with this code already exists.']);
        }

        $milestones = [];
        foreach ($data['milestone_titles'] as $i => $title) {
            $milestones[] = [
                'title' => $title,
                'type' => $data['milestone_types'][$i] ?? 'generic',
                'description' => $request->input("milestone_descriptions.$i"),
                'due_in_months' => (int) ($data['milestone_due_months'][$i] ?? 0),
                'required_value' => (float) ($data['milestone_required_values'][$i] ?? 0),
                'unit' => $data['milestone_units'][$i] ?? 'count',
                'evidence_required' => true,
            ];
        }

        PolicyTemplate::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'milestones' => $milestones,
            'rules' => [
                'warning_days' => (int) $data['warning_days'],
                'escalation_after_days' => 1,
            ],
        ]);

        return redirect()->route('policies.index')->with('success', 'Policy template created.');
    }
}
