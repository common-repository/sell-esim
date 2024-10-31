<?php

namespace tsim\expend\rest_api\open\v1;

use tsim\expend\rest_api\Base;

class Devices extends Base
{

    const SupportList = [
        "Apple" => [
            "iPhone 15, 15 Plus, 15 Pro, 15 Pro Max",
            "iPhone 14, 14 Plus, 14 Pro, 14 Pro Max",
            "iPhone 13, 13 Pro, 13 Pro Max, 13 Mini",
            "iPhone 12, 12 Pro, 12 Pro Max, 12 Mini",
            "iPhone 11, 11 Pro, 11 Pro Max",
            "iPhone SE, SE2 and SE3",
            "iPhone XS, XS Max",
            "iPhone XR",
            "iPad Pro 12.9‑inch (3rd gen) – onwards",
            "iPad Pro 11‑inch (1st gen) – onwards",
            "iPad Air (3rd gen) – onwards",
            "iPad (7th gen) – onwards",
            "iPad mini (5th gen) – onwards"
        ],
        "Google Pixel" => [
            "Google Pixel Fold",
            "Google Pixel 8, 8 Pro",
            "Google Pixel 7, 7a, 7 Pro",
            "Google Pixel 6, 6a, 6 Pro",
            "Google Pixel 5, 5a",
            "Google Pixel 4, 4a, 4XL",
            "Google Pixel 3a, 3XL (SEA is not compatible)"
        ],
        "Samsung" => [
            "Samsung Galaxy S24, S24+, S24 Ultra",
            "Samsung Galaxy S23, S23+, S23 Ultra",
            "Samsung Galaxy S22, S22+, S22 Ultra",
            "Samsung Galaxy S21, S21+, S21 Ultra",
            "Samsung Galaxy S20, S20+, S20 Ultra",
            "Samsung Z Fold, Fold2, Fold3, Fold4, Fold5",
            "Samsung Galaxy Z Flip, Flip3, Flip4, Flip5",
            "Samsung Galaxy Note 20, Note 20 Ultra"
        ],
        "OPPO" => [
            "Oppo Find X3 Pro",
            "Oppo Reno 5A",
            "Oppo Find N2 Flip",
            "Oppo Find X5",
            "Oppo Find X5 Pro"
        ],
        "Huawei" => [
            "Huawei P40",
            "Huawei P40 Pro",
            "Huawei Mate 40 Pro"
        ],
        "Xiaomi" => [
            "Xiaomi 12T Pro",
            "Xiaomi 13, 13 Lite, 13 Pro",
            "Xiaomi 14 Pro"
        ],
        "Others" => [
            "Motorola Razr 2019",
            "Motorola Razr 2022, 5G",
            "Motorola Razr 40, 40 Ultra",
            "Motorola Edge+, Edge 40, Pro, Neo",
            "Motorola G53, G54",
            "Gemini PDA",
            "Rakuten Mini, Big, Big-S",
            "Rakuten Hand, Hand 5G",
            "Sony Xperia 10 III Lite",
            "Sony Xperia 10 IV, 10 V",
            "Sony Xperia 1 V, 1 IV, 5 IV, 5 V",
            "Sony Xperia Ace III",
            "Surface Pro X",
            "Honor 90, Honor X8",
            "Honor Magic 4 Pro, 5 Pro, 6 Pro",
            "Fairphone 4",
            "Sharp Aquos Sense6s",
            "Sharp Aquos Wish",
            "Nokia XR21, X30, G60 5G",
            "Oneplus Open, OnePlus 11, OnePlus 12"
        ]
    ];

    public function supportDevices(\WP_REST_Request $request)
    {
        return $this->result(self::SupportList);
    }
}