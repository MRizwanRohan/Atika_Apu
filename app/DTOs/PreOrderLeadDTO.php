<?php

namespace App\DTOs;

class PreOrderLeadDTO
{
    public ?string $name;
    public ?string $email;
    public ?string $phone;
    public string $productInterest;
    public ?string $referenceLink;
    public ?string $message;
    public ?string $imagePath;
    public string $status;

    public function __construct(
        ?string $name,
        ?string $email,
        ?string $phone,
        string $productInterest,
        ?string $referenceLink,
        ?string $message,
        ?string $imagePath,
        string $status = 'pending'
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->productInterest = $productInterest;
        $this->referenceLink = $referenceLink;
        $this->message = $message;
        $this->imagePath = $imagePath;
        $this->status = $status;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['product_interest'],
            $data['reference_link'] ?? null,
            $data['message'] ?? null,
            $data['image_path'] ?? null,
            $data['status'] ?? 'pending'
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'product_interest' => $this->productInterest,
            'reference_link' => $this->referenceLink,
            'message' => $this->message,
            'image_path' => $this->imagePath,
            'status' => $this->status,
        ];
    }
}