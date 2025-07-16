<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'shipping_address' => 'required|string|max:500',
            'billing_address' => 'nullable|string|max:500',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:card,cash_on_delivery,bank_transfer',
            'payment_data' => 'nullable|array',
            'payment_data.card_number' => 'required_if:payment_method,card|string',
            'payment_data.expiry_month' => 'required_if:payment_method,card|integer|between:1,12',
            'payment_data.expiry_year' => 'required_if:payment_method,card|integer|min:' . date('Y'),
            'payment_data.cvv' => 'required_if:payment_method,card|string|size:3',
            'payment_data.cardholder_name' => 'required_if:payment_method,card|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shipping_address.required' => 'L\'adresse de livraison est obligatoire.',
            'shipping_address.max' => 'L\'adresse de livraison ne peut pas dépasser 500 caractères.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'payment_method.required' => 'Le mode de paiement est obligatoire.',
            'payment_method.in' => 'Le mode de paiement sélectionné n\'est pas valide.',
            'payment_data.card_number.required_if' => 'Le numéro de carte est obligatoire pour le paiement par carte.',
            'payment_data.expiry_month.required_if' => 'Le mois d\'expiration est obligatoire.',
            'payment_data.expiry_month.between' => 'Le mois d\'expiration doit être entre 1 et 12.',
            'payment_data.expiry_year.required_if' => 'L\'année d\'expiration est obligatoire.',
            'payment_data.expiry_year.min' => 'L\'année d\'expiration ne peut pas être dans le passé.',
            'payment_data.cvv.required_if' => 'Le code CVV est obligatoire.',
            'payment_data.cvv.size' => 'Le code CVV doit contenir exactement 3 chiffres.',
            'payment_data.cardholder_name.required_if' => 'Le nom du porteur de carte est obligatoire.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Si l'adresse de facturation n'est pas fournie, utiliser l'adresse de livraison
        if (empty($this->billing_address)) {
            $this->merge([
                'billing_address' => $this->shipping_address
            ]);
        }
    }
}