<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Http\Requests\StorePbcTemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PbcTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PbcTemplate::with(['creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $is_active = $request->status === 'active';
            $query->where('is_active', $is_active);
        }

        $templates = $query->latest()->paginate(15);

        return view('admin.pbc-templates.index', compact('templates'));
    }

    public function create()
    {
        // Default audit template structure
        $defaultItems = [
            ['category' => 'Permanent File', 'particulars' => 'Latest Articles of Incorporation and By-laws', 'is_required' => true],
            ['category' => 'Permanent File', 'particulars' => 'BIR Certificate of Registration', 'is_required' => true],
            ['category' => 'Permanent File', 'particulars' => 'Latest General Information Sheet filed with the SEC', 'is_required' => true],
            ['category' => 'Permanent File', 'particulars' => 'Stock transfer book', 'is_required' => true],
            ['category' => 'Permanent File', 'particulars' => 'Minutes of meetings of the stockholders, board of directors, and executive committee held during the period', 'is_required' => true],
            ['category' => 'Current File', 'particulars' => 'Trial Balance as of year-end', 'is_required' => true],
            ['category' => 'Current File', 'particulars' => 'General Ledger (all accounts)', 'is_required' => true],
            ['category' => 'Current File', 'particulars' => 'Bank statements and reconciliations', 'is_required' => true],
            ['category' => 'Current File', 'particulars' => 'Accounts receivable aging', 'is_required' => true],
            ['category' => 'Current File', 'particulars' => 'Accounts payable aging', 'is_required' => true],
        ];

        return view('admin.pbc-templates.create', compact('defaultItems'));
    }

    public function store(StorePbcTemplateRequest $request)
    {
        DB::transaction(function () use ($request) {
            // Create template
            $template = PbcTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'header_info' => $request->header_info,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id(),
            ]);

            // Create template items
            foreach ($request->items as $index => $item) {
                PbcTemplateItem::create([
                    'pbc_template_id' => $template->id,
                    'category' => $item['category'] ?? null,
                    'particulars' => $item['particulars'],
                    'is_required' => $item['is_required'] ?? true,
                    'order_index' => $index,
                ]);
            }
        });

        return redirect()
            ->route('admin.pbc-templates.index')
            ->with('success', 'PBC Template created successfully.');
    }

    public function show(PbcTemplate $pbcTemplate)
    {
        $pbcTemplate->load(['templateItems' => function($query) {
            $query->orderBy('order_index');
        }, 'creator', 'pbcRequests']);

        return view('admin.pbc-templates.show', compact('pbcTemplate'));
    }

    public function edit(PbcTemplate $pbcTemplate)
    {
        $pbcTemplate->load(['templateItems' => function($query) {
            $query->orderBy('order_index');
        }]);

        return view('admin.pbc-templates.edit', compact('pbcTemplate'));
    }

    public function update(StorePbcTemplateRequest $request, PbcTemplate $pbcTemplate)
    {
        DB::transaction(function () use ($request, $pbcTemplate) {
            // Update template
            $pbcTemplate->update([
                'name' => $request->name,
                'description' => $request->description,
                'header_info' => $request->header_info,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Delete existing items and recreate
            $pbcTemplate->templateItems()->delete();

            // Create new template items
            foreach ($request->items as $index => $item) {
                PbcTemplateItem::create([
                    'pbc_template_id' => $pbcTemplate->id,
                    'category' => $item['category'] ?? null,
                    'particulars' => $item['particulars'],
                    'is_required' => $item['is_required'] ?? true,
                    'order_index' => $index,
                ]);
            }
        });

        return redirect()
            ->route('admin.pbc-templates.index')
            ->with('success', 'PBC Template updated successfully.');
    }

    public function destroy(PbcTemplate $pbcTemplate)
    {
        // Check if template is being used in any requests
        if ($pbcTemplate->pbcRequests()->count() > 0) {
            return redirect()
                ->route('admin.pbc-templates.index')
                ->with('error', 'Cannot delete template that is being used in PBC requests.');
        }

        $pbcTemplate->delete();

        return redirect()
            ->route('admin.pbc-templates.index')
            ->with('success', 'PBC Template deleted successfully.');
    }

    // AJAX endpoint for getting template items
    public function getTemplateItems(PbcTemplate $pbcTemplate)
    {
    try {
        // Use the same direct SQL that works in the test route
        $items = \DB::table('pbc_template_items')
            ->where('pbc_template_id', $pbcTemplate->id)
            ->orderBy('order_index')
            ->select('id', 'category', 'particulars', 'is_required', 'order_index')
            ->get();

        if ($items->count() === 0) {
            return response()->json([
                'message' => 'No items found',
                'data' => [],
                'debug_info' => [
                    'template_id' => $pbcTemplate->id,
                    'template_name' => $pbcTemplate->name
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
            'debug_info' => [
                'template_id' => $pbcTemplate->id,
                'template_name' => $pbcTemplate->name
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    // Toggle template active status
    public function toggleStatus(PbcTemplate $pbcTemplate)
    {
        $pbcTemplate->update([
            'is_active' => !$pbcTemplate->is_active
        ]);

        $status = $pbcTemplate->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.pbc-templates.index')
            ->with('success', "Template {$status} successfully.");
    }
}

