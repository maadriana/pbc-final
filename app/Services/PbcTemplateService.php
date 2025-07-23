<?php

namespace App\Services;

use App\Models\PbcTemplate;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;

class PbcTemplateService
{
    /**
     * Create PBC Request from Template
     */
    public function createRequestFromTemplate(PbcTemplate $template, array $requestData)
    {
        // Create the PBC request
        $pbcRequest = PbcRequest::create([
            'template_id' => $template->id,
            'client_id' => $requestData['client_id'],
            'project_id' => $requestData['project_id'],
            'title' => $requestData['title'],
            'description' => $requestData['description'] ?? null,
            'header_info' => array_merge($template->header_info ?? [], $requestData['header_info'] ?? []),
            'due_date' => $requestData['due_date'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Copy template items to request items
        foreach ($template->templateItems as $templateItem) {
            PbcRequestItem::create([
                'pbc_request_id' => $pbcRequest->id,
                'category' => $templateItem->category,
                'particulars' => $templateItem->particulars,
                'date_requested' => now()->toDateString(),
                'is_required' => $templateItem->is_required,
                'order_index' => $templateItem->order_index,
            ]);
        }

        return $pbcRequest;
    }

    /**
     * Get default audit template items
     */
    public static function getDefaultAuditItems()
    {
        return [
            // Permanent File Items
            [
                'category' => 'Permanent File',
                'particulars' => 'Latest Articles of Incorporation and By-laws',
                'is_required' => true,
                'order_index' => 1
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'BIR Certificate of Registration',
                'is_required' => true,
                'order_index' => 2
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Latest General Information Sheet filed with the SEC',
                'is_required' => true,
                'order_index' => 3
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Stock transfer book',
                'is_required' => true,
                'order_index' => 4
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Minutes of meetings of the stockholders, board of directors, and executive committee held during the period from January 1, ____ to date',
                'is_required' => true,
                'order_index' => 5
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Contracts and other agreements with accounting significance held/entered into during the year such as but not limited to: Construction contracts, Loan Agreements, Lease Agreements, Others',
                'is_required' => true,
                'order_index' => 6
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Completed Letters to Lawyer and to the Corporate Secretary using the Company\'s letterhead',
                'is_required' => true,
                'order_index' => 7
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Pending tax assessments, if any',
                'is_required' => false,
                'order_index' => 8
            ],
            [
                'category' => 'Permanent File',
                'particulars' => 'Prior years audited financial statement',
                'is_required' => true,
                'order_index' => 9
            ],

            // Current File Items
            [
                'category' => 'Current File',
                'particulars' => 'Trial Balance as of December 31, ____',
                'is_required' => true,
                'order_index' => 10
            ],
            [
                'category' => 'Current File',
                'particulars' => 'General Ledger (all accounts) from January 1, ____ to December 31, ____',
                'is_required' => true,
                'order_index' => 11
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Cash and bank statements and reconciliations for the period',
                'is_required' => true,
                'order_index' => 12
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Accounts receivable aging as of December 31, ____',
                'is_required' => true,
                'order_index' => 13
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Accounts payable aging as of December 31, ____',
                'is_required' => true,
                'order_index' => 14
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Inventory count sheets and supporting computations',
                'is_required' => true,
                'order_index' => 15
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Fixed assets schedule with additions and disposals during the year',
                'is_required' => true,
                'order_index' => 16
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Depreciation schedule for all fixed assets',
                'is_required' => true,
                'order_index' => 17
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Schedule of prepaid expenses and other assets',
                'is_required' => true,
                'order_index' => 18
            ],
            [
                'category' => 'Current File',
                'particulars' => 'Schedule of accrued expenses and other liabilities',
                'is_required' => true,
                'order_index' => 19
            ],

            // Tax and Statutory Requirements
            [
                'category' => 'Tax and Statutory',
                'particulars' => 'Income Tax Return (ITR) for the year ____',
                'is_required' => true,
                'order_index' => 20
            ],
            [
                'category' => 'Tax and Statutory',
                'particulars' => 'Monthly VAT Returns (BIR Form 2550M) for the year ____',
                'is_required' => true,
                'order_index' => 21
            ],
            [
                'category' => 'Tax and Statutory',
                'particulars' => 'Quarterly VAT Returns (BIR Form 2550Q) for the year ____',
                'is_required' => true,
                'order_index' => 22
            ],
            [
                'category' => 'Tax and Statutory',
                'particulars' => 'Monthly withholding tax returns for the year ____',
                'is_required' => true,
                'order_index' => 23
            ],
            [
                'category' => 'Tax and Statutory',
                'particulars' => 'Annual information return of income payments (BIR Form 1604C)',
                'is_required' => true,
                'order_index' => 24
            ],
            [
                'category' => 'Tax and Statutory',
                'particulars' => 'SSS, PhilHealth, and Pag-IBIG contributions and remittances',
                'is_required' => true,
                'order_index' => 25
            ]
        ];
    }

    /**
     * Get template statistics
     */
    public function getTemplateStats(PbcTemplate $template)
    {
        return [
            'total_items' => $template->templateItems()->count(),
            'required_items' => $template->templateItems()->where('is_required', true)->count(),
            'optional_items' => $template->templateItems()->where('is_required', false)->count(),
            'categories' => $template->templateItems()
                ->whereNotNull('category')
                ->distinct()
                ->count('category'),
            'total_requests' => $template->pbcRequests()->count(),
            'active_requests' => $template->pbcRequests()
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
        ];
    }
}
