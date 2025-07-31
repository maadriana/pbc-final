<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_template_id',
        'category',
        'particulars',
        'is_required',
        'order_index'
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    // NEW: Category constants for wireframe alignment
    const CATEGORY_CURRENT_FILE = 'CF';
    const CATEGORY_PERMANENT_FILE = 'PF';

    public static function getCategories()
    {
        return [
            self::CATEGORY_CURRENT_FILE => 'Current File',
            self::CATEGORY_PERMANENT_FILE => 'Permanent File',
        ];
    }

    // NEW: Get all available category options for dropdowns
    public static function getCategoryOptions()
    {
        return [
            ['value' => self::CATEGORY_CURRENT_FILE, 'label' => 'Current File (CF)', 'description' => 'Documents related to current year operations'],
            ['value' => self::CATEGORY_PERMANENT_FILE, 'label' => 'Permanent File (PF)', 'description' => 'Permanent documents like articles, registrations'],
        ];
    }

    // Relationships
    public function template()
    {
        return $this->belongsTo(PbcTemplate::class, 'pbc_template_id');
    }

    // NEW: Relationship to see how many times this template item has been used
    public function requestItems()
    {
        return $this->hasMany(PbcRequestItem::class, 'particulars', 'particulars');
    }

    // NEW: Helper methods for category display
    public function getCategoryDisplayAttribute()
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    public function getCategoryColorClass()
    {
        return match($this->category) {
            self::CATEGORY_CURRENT_FILE => 'badge-primary',
            self::CATEGORY_PERMANENT_FILE => 'badge-secondary',
            default => 'badge-light'
        };
    }

    public function getCategoryIconClass()
    {
        return match($this->category) {
            self::CATEGORY_CURRENT_FILE => 'fas fa-file',
            self::CATEGORY_PERMANENT_FILE => 'fas fa-folder',
            default => 'fas fa-file-alt'
        };
    }

    // NEW: Get category description for UI
    public function getCategoryDescription()
    {
        return match($this->category) {
            self::CATEGORY_CURRENT_FILE => 'Documents related to current year operations and transactions',
            self::CATEGORY_PERMANENT_FILE => 'Permanent documents like articles of incorporation, registrations, contracts',
            default => 'Document category not specified'
        };
    }

    // NEW: Scope for filtering by category
    public function scopeCurrentFile($query)
    {
        return $query->where('category', self::CATEGORY_CURRENT_FILE);
    }

    public function scopePermanentFile($query)
    {
        return $query->where('category', self::CATEGORY_PERMANENT_FILE);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // NEW: Scope for required/optional items
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    // NEW: Scope for ordering
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeByCategoryOrdered($query, $category)
    {
        return $query->where('category', $category)->orderBy('order_index');
    }

    // NEW: Helper methods for wireframe compatibility
    public function getRequiredDisplayAttribute()
    {
        return $this->is_required ? 'Required' : 'Optional';
    }

    public function getRequiredBadgeClass()
    {
        return $this->is_required ? 'badge-danger' : 'badge-info';
    }

    public function getRequiredIconClass()
    {
        return $this->is_required ? 'fas fa-exclamation-circle text-danger' : 'fas fa-info-circle text-info';
    }

    // NEW: Get shortened particulars for display
    public function getParticularsShortAttribute()
    {
        return strlen($this->particulars) > 50
            ? substr($this->particulars, 0, 50) . '...'
            : $this->particulars;
    }

    // NEW: Get formatted order for display
    public function getOrderDisplayAttribute()
    {
        return str_pad($this->order_index + 1, 2, '0', STR_PAD_LEFT);
    }

    // NEW: Check if this template item is used in any requests
    public function isUsedInRequests()
    {
        return PbcRequestItem::where('particulars', $this->particulars)->exists();
    }

    // NEW: Get usage count
    public function getUsageCount()
    {
        return PbcRequestItem::where('particulars', $this->particulars)->count();
    }

    // NEW: Get completion rate across all requests using this item
    public function getCompletionRate()
    {
        $totalUsage = $this->getUsageCount();
        if ($totalUsage === 0) {
            return 0;
        }

        $completedUsage = PbcRequestItem::where('particulars', $this->particulars)
            ->whereHas('documents', function($query) {
                $query->where('status', 'approved');
            })
            ->count();

        return round(($completedUsage / $totalUsage) * 100);
    }

    // NEW: Static method to get standard audit template items
    public static function getStandardAuditItems()
    {
        return [
            // Permanent File Items
            [
                'category' => self::CATEGORY_PERMANENT_FILE,
                'particulars' => 'Latest Articles of Incorporation and By-laws',
                'is_required' => true,
                'order_index' => 1
            ],
            [
                'category' => self::CATEGORY_PERMANENT_FILE,
                'particulars' => 'BIR Certificate of Registration',
                'is_required' => true,
                'order_index' => 2
            ],
            [
                'category' => self::CATEGORY_PERMANENT_FILE,
                'particulars' => 'Latest General Information Sheet filed with the SEC',
                'is_required' => true,
                'order_index' => 3
            ],
            [
                'category' => self::CATEGORY_PERMANENT_FILE,
                'particulars' => 'Stock transfer book',
                'is_required' => true,
                'order_index' => 4
            ],
            [
                'category' => self::CATEGORY_PERMANENT_FILE,
                'particulars' => 'Minutes of meetings of the stockholders, board of directors, and executive committee',
                'is_required' => true,
                'order_index' => 5
            ],

            // Current File Items
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Trial Balance as of December 31, ____',
                'is_required' => true,
                'order_index' => 6
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'General Ledger (all accounts) from January 1, ____ to December 31, ____',
                'is_required' => true,
                'order_index' => 7
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Cash and bank statements and reconciliations for the period',
                'is_required' => true,
                'order_index' => 8
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Accounts receivable aging as of December 31, ____',
                'is_required' => true,
                'order_index' => 9
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Accounts payable aging as of December 31, ____',
                'is_required' => true,
                'order_index' => 10
            ],
        ];
    }

    // NEW: Static method to get standard accounting template items
    public static function getStandardAccountingItems()
    {
        return [
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Monthly bank statements and reconciliations',
                'is_required' => true,
                'order_index' => 1
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Sales invoices and receipts',
                'is_required' => true,
                'order_index' => 2
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Purchase invoices and receipts',
                'is_required' => true,
                'order_index' => 3
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Payroll records and related documents',
                'is_required' => true,
                'order_index' => 4
            ],
        ];
    }

    // NEW: Static method to get standard tax template items
    public static function getStandardTaxItems()
    {
        return [
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Annual Income Tax Return (ITR)',
                'is_required' => true,
                'order_index' => 1
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Monthly VAT Returns (BIR Form 2550M)',
                'is_required' => true,
                'order_index' => 2
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Quarterly VAT Returns (BIR Form 2550Q)',
                'is_required' => true,
                'order_index' => 3
            ],
            [
                'category' => self::CATEGORY_CURRENT_FILE,
                'particulars' => 'Monthly withholding tax returns',
                'is_required' => true,
                'order_index' => 4
            ],
        ];
    }

    // NEW: Convert template item to request item format
    public function toRequestItem($pbcRequestId, $dateRequested = null)
    {
        return [
            'pbc_request_id' => $pbcRequestId,
            'category' => $this->category,
            'particulars' => $this->particulars,
            'date_requested' => $dateRequested ?? now()->toDateString(),
            'is_required' => $this->is_required,
            'order_index' => $this->order_index,
            'status' => 'pending',
        ];
    }

    // NEW: Validation rules for template items
    public static function getValidationRules()
    {
        return [
            'category' => 'nullable|in:CF,PF',
            'particulars' => 'required|string|max:1000',
            'is_required' => 'boolean',
            'order_index' => 'integer|min:0',
        ];
    }

    // NEW: Get category statistics for a template
    public function getTemplateCategoryStats()
    {
        $templateId = $this->pbc_template_id;

        return [
            'total_items' => self::where('pbc_template_id', $templateId)->count(),
            'cf_items' => self::where('pbc_template_id', $templateId)->where('category', 'CF')->count(),
            'pf_items' => self::where('pbc_template_id', $templateId)->where('category', 'PF')->count(),
            'required_items' => self::where('pbc_template_id', $templateId)->where('is_required', true)->count(),
            'optional_items' => self::where('pbc_template_id', $templateId)->where('is_required', false)->count(),
        ];
    }

    // NEW: Clone this template item to another template
    public function cloneToTemplate($targetTemplateId, $newOrderIndex = null)
    {
        return self::create([
            'pbc_template_id' => $targetTemplateId,
            'category' => $this->category,
            'particulars' => $this->particulars,
            'is_required' => $this->is_required,
            'order_index' => $newOrderIndex ?? $this->order_index,
        ]);
    }

    // NEW: Get similar items from other templates
    public function getSimilarItems($limit = 5)
    {
        return self::where('pbc_template_id', '!=', $this->pbc_template_id)
            ->where('category', $this->category)
            ->where('particulars', 'LIKE', '%' . substr($this->particulars, 0, 20) . '%')
            ->limit($limit)
            ->get();
    }
}
