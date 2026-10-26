<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class TaiKhoan extends Model
{
    protected $table = 'tai_khoans';

    protected $fillable = [
        'ho_ten',
        'anh_dai_dien',
        'ngay_sinh',
        'email',
        'so_dien_thoai',
        'gioi_tinh',
        'size_thuong_mac',
        'chieu_cao',
        'can_nang',
        'phong_cach',
        'mat_khau',
        'chuc_vu_id',
        'nhan_khach_hang',
        'ghi_chu_admin',
        'trang_thai',
        'ngay_tao',
        'dang_nhap_cuoi',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected function casts(): array
    {
        return [
            'ngay_sinh'      => 'date',
            'ngay_tao'       => 'datetime',
            'dang_nhap_cuoi' => 'datetime',
            'trang_thai'     => 'boolean',
            'gioi_tinh'      => 'integer',
            'chieu_cao'      => 'integer',
            'can_nang'       => 'integer',
            'mat_khau'       => 'hashed',
        ];
    }

    public $timestamps = false;

    // ─── Relationships ──────────────────────────────────────────

    public function chucVu(): BelongsTo
    {
        return $this->belongsTo(ChucVu::class, 'chuc_vu_id');
    }

    public function diaChis(): HasMany
    {
        return $this->hasMany(DiaChi::class, 'tai_khoan_id');
    }

    public function diaChiMacDinh(): HasOne
    {
        return $this->hasOne(DiaChi::class, 'tai_khoan_id')->where('mac_dinh', true);
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'tai_khoan_id')->orderByDesc('ngay_dat');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'tai_khoan_id')->orderByDesc('ngay_tao');
    }

    // ─── Accessors & Helpers ────────────────────────────────────

    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->anh_dai_dien)) {
            return null;
        }

        if (str_starts_with($this->anh_dai_dien, 'http://') || str_starts_with($this->anh_dai_dien, 'https://')) {
            return $this->anh_dai_dien;
        }

        return Storage::disk('public')->url($this->anh_dai_dien);
    }

    /**
     * Tính BMI thời trang dựa trên chiều cao & cân nặng
     */
    public function getBmiAttribute(): ?array
    {
        if (!$this->chieu_cao || !$this->can_nang || $this->chieu_cao <= 0) {
            return null;
        }

        $heightInM = $this->chieu_cao / 100;
        $score = round($this->can_nang / ($heightInM * $heightInM), 1);

        $label = 'Cân đối';
        $badge = 'success';
        if ($score < 18.5) {
            $label = 'Gầy (Form ôm)';
            $badge = 'warning';
        } elseif ($score >= 25 && $score < 30) {
            $label = 'Hơi đậm (Form suông / Oversize)';
            $badge = 'info';
        } elseif ($score >= 30) {
            $label = 'Ngoại cỡ (Big size)';
            $badge = 'danger';
        }

        return [
            'score' => $score,
            'label' => $label,
            'badge' => $badge,
        ];
    }

    /**
     * Hạng thành viên Loyalty Tier dựa trên tổng chi tiêu
     */
    public function getLoyaltyTierAttribute(): array
    {
        $totalSpent = (float) $this->donHangs()->sum('tong_tien');

        if ($totalSpent >= 10000000) {
            return [
                'name'        => 'Kim Cương',
                'code'        => 'diamond',
                'badge_class' => 'badge-primary',
                'bg_gradient' => 'linear-gradient(135deg, #00c6ff, #0072ff)',
                'icon'        => 'fas fa-gem',
                'discount'    => 'Giảm 10% mọi đơn',
                'progress'    => 100,
                'next_tier'   => null,
                'needed'      => 0,
                'total_spent' => $totalSpent,
            ];
        }

        if ($totalSpent >= 3000000) {
            $needed = 10000000 - $totalSpent;
            $progress = round(($totalSpent / 10000000) * 100);
            return [
                'name'        => 'Vàng',
                'code'        => 'gold',
                'badge_class' => 'badge-warning',
                'bg_gradient' => 'linear-gradient(135deg, #f6d365, #fda085)',
                'icon'        => 'fas fa-crown',
                'discount'    => 'Giảm 5% mọi đơn',
                'progress'    => $progress,
                'next_tier'   => 'Kim Cương',
                'needed'      => $needed,
                'total_spent' => $totalSpent,
            ];
        }

        if ($totalSpent >= 1000000) {
            $needed = 3000000 - $totalSpent;
            $progress = round(($totalSpent / 3000000) * 100);
            return [
                'name'        => 'Bạc',
                'code'        => 'silver',
                'badge_class' => 'badge-secondary',
                'bg_gradient' => 'linear-gradient(135deg, #e0e0e0, #bdbdbd)',
                'icon'        => 'fas fa-medal',
                'discount'    => 'Giảm 3% mọi đơn',
                'progress'    => $progress,
                'next_tier'   => 'Vàng',
                'needed'      => $needed,
                'total_spent' => $totalSpent,
            ];
        }

        $needed = 1000000 - $totalSpent;
        $progress = round(($totalSpent / 1000000) * 100);
        return [
            'name'        => 'Đồng',
            'code'        => 'bronze',
            'badge_class' => 'badge-light border',
            'bg_gradient' => 'linear-gradient(135deg, #d7d2cc, #304352)',
            'icon'        => 'fas fa-shield-alt',
            'discount'    => 'Thành viên mới',
            'progress'    => min(100, max(5, $progress)),
            'next_tier'   => 'Bạc',
            'needed'      => $needed,
            'total_spent' => $totalSpent,
        ];
    }

    /**
     * RBAC Helpers
     */
    public function isAdmin(): bool
    {
        return $this->chuc_vu_id === 1;
    }

    public function isStaff(): bool
    {
        return in_array($this->chuc_vu_id, [2, 3, 4], true);
    }

    public function isCustomer(): bool
    {
        return !$this->isAdmin() && !$this->isStaff();
    }
}
