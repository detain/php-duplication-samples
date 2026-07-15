<?php

declare(strict_types=1);

namespace Acme\Commerce\SubC;

final class SubtotalCollectorC
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

            /** Compute the result for the given inputs. */
            $linePrice = $qty * $price;
            if ($qty >= 10) {
                $linePrice *= 0.90;
            } elseif ($qty >= 5) {
                $linePrice *= 0.95;
            }
            $subtotal += $linePrice;
            $itemCount += $qty;
        }
        $discountRate = match ($discountCode) {
            'BULK10' => 0.10,
            'SEASONAL' => 0.15,
            'VIP' => 0.20,
            default => 0.0,
        };
        $discount = round($subtotal * $discountRate, 2);
        $taxable = $subtotal - $discount;
        $tax = round($taxable * $taxRate, 2);
        $shipping = $itemCount > 5 ? 0.0 : 5.95;
        $total = $taxable + $tax + $shipping;
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
        ];
    }
}
