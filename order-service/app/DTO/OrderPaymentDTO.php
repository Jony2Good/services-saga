<?php

namespace App\DTO;

class OrderPaymentDTO {
     public function __construct(
        public int $user_id,
        public int $order_id,
        public string $email,
        public int $total_price = 0
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            user_id: $data["user_id"],
            order_id: $data["order_id"],
            email: $data["email"],
            total_price: $data["total_price"] ?? 0,
        );
    }

    public function toArray():array {
        return [
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'email' => $this->email,
            'total_price' => $this->total_price
        ];
    }
}