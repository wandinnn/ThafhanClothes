<?php

namespace App\Exceptions;

use Exception;

/**
 * Dilempar ketika stok produk tidak cukup untuk menyelesaikan sebuah pesanan.
 */
class InsufficientStockException extends Exception {}
