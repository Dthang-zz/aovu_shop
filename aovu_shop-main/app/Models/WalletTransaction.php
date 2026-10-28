<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $table = 'giao_dich_vis';

    public $timestamps = false;

    protected $fillable = [
        'vi_id',
        'loai_giao_dich',
        'so_tien',
        'so_du_truoc',
        'so_du_sau',
        'don_hang_id',
        'yeu_cau_doi_tra_id',
        'ghi_chu',
        'nguoi_thuc_hien_id',
        'ngay_tao',
    ];

    protected function casts(): array
    {
        return [
            'vi_id'               => 'integer',
            'loai_giao_dich'      => 'integer',
            'so_tien'             => 'decimal:0',
            'so_du_truoc'         => 'decimal:0',
            'so_du_sau'           => 'decimal:0',
            'don_hang_id'         => 'integer',
            'yeu_cau_doi_tra_id'  => 'integer',
            'nguoi_thuc_hien_id'  => 'integer',
            'ngay_tao'            => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'vi_id');
    }

    public function typeLabel(): string
    {
        return Wallet::transactionTypes()[$this->loai_giao_dich] ?? 'Không xác định';
    }

    public function typeBadge(): string
    {
        return Wallet::transactionBadges()[$this->loai_giao_dich] ?? 'secondary';
    }

    public function typeIcon(): string
    {
        return Wallet::transactionIcons()[$this->loai_giao_dich] ?? 'fas fa-question';
    }

    /**
     * Kiểm tra giao dịch là nạp tiền vào (dương) hay rút tiền ra (âm).
     */
    public function isCredit(): bool
    {
        return $this->loai_giao_dich === Wallet::TRANSACTION_REFUND
            || ($this->loai_giao_dich === Wallet::TRANSACTION_ADJUST && $this->so_tien > 0);
    }
}
