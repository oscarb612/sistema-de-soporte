@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Nuevo presupuesto</h1>
        <p class="muted">Orden {{ $order->code }} · {{ $order->client->name }}</p>
    </div>
    <a class="btn" href="{{ route('repair-orders.show', $order) }}">Volver a la orden</a>
</div>

<div class="panel">
    <form method="post" action="{{ route('repair-orders.quotes.store', $order) }}">
        @csrf

        <div class="form-grid">
            <label class="full">Diagnóstico para el presupuesto
                <textarea name="diagnosis_summary" required>{{ old('diagnosis_summary', $order->technical_diagnosis) }}</textarea>
            </label>
            <label>Válido hasta
                <input type="date" name="valid_until" value="{{ old('valid_until', now()->addDays(5)->format('Y-m-d')) }}">
            </label>
            <label>Descuento
                <input id="discount_amount" type="number" min="0" step="1" name="discount_amount" value="{{ old('discount_amount', 0) }}">
            </label>
            <label class="full">Notas internas
                <textarea name="internal_notes">{{ old('internal_notes') }}</textarea>
            </label>
        </div>

        <div class="page-head" style="margin-top:24px; margin-bottom:12px;">
            <h2 style="margin:0;">Items del presupuesto</h2>
            <button id="add_quote_item" class="btn primary" type="button">Agregar item</button>
        </div>

        <div id="quote_items" class="grid">
            @foreach (old('items', $items) as $index => $item)
                <div class="panel quote-item" data-quote-item>
                    <div class="form-grid">
                        <label>Tipo
                            <select name="items[{{ $index }}][type]">
                                @foreach (['mano_obra' => 'Mano de obra', 'repuesto' => 'Repuesto', 'servicio' => 'Servicio'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($item['type'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Descripción
                            <input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" required>
                        </label>
                        <label>Cantidad
                            <input class="quote-calc" type="number" min="1" step="1" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required>
                        </label>
                        <label>Precio unitario neto
                            <input class="quote-calc" type="number" min="0" step="1" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" required>
                        </label>
                    </div>
                    <div class="actions">
                        <strong>Subtotal: $<span data-item-total>0</span></strong>
                        <button class="btn danger remove-quote-item" type="button" @if ($loop->first) style="display:none;" @endif>Eliminar ítem</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="panel" style="margin-top:16px;">
            <p><strong>Neto:</strong> $<span id="quote_net">0</span></p>
            <p><strong>IVA (19%):</strong> $<span id="quote_tax">0</span></p>
            <h2>Total: $<span id="quote_total">0</span></h2>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Emitir presupuesto</button>
            <a class="btn" href="{{ route('repair-orders.show', $order) }}">Cancelar</a>
        </div>
    </form>
</div>

<template id="quote_item_template">
    <div class="panel quote-item" data-quote-item>
        <div class="form-grid">
            <label>Tipo
                <select name="items[__INDEX__][type]">
                    <option value="mano_obra">Mano de obra</option>
                    <option value="repuesto">Repuesto</option>
                    <option value="servicio">Servicio</option>
                </select>
            </label>
            <label>Descripción
                <input name="items[__INDEX__][description]" required>
            </label>
            <label>Cantidad
                <input class="quote-calc" type="number" min="1" step="1" name="items[__INDEX__][quantity]" value="1" required>
            </label>
            <label>Precio unitario neto
                <input class="quote-calc" type="number" min="0" step="1" name="items[__INDEX__][unit_price]" value="0" required>
            </label>
        </div>
        <div class="actions">
            <strong>Subtotal: $<span data-item-total>0</span></strong>
            <button class="btn danger remove-quote-item" type="button">Eliminar ítem</button>
        </div>
    </div>
</template>

<script>
    (() => {
        const container = document.getElementById('quote_items');
        const addButton = document.getElementById('add_quote_item');
        const template = document.getElementById('quote_item_template');
        const discount = document.getElementById('discount_amount');
        const net = document.getElementById('quote_net');
        const tax = document.getElementById('quote_tax');
        const total = document.getElementById('quote_total');

        const money = (value) => new Intl.NumberFormat('es-CL').format(Math.max(value, 0));

        const refreshIndexes = () => {
            container.querySelectorAll('[data-quote-item]').forEach((item, index) => {
                item.querySelectorAll('input, select').forEach((field) => {
                    field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
                });

                item.querySelector('.remove-quote-item').style.display = index === 0 ? 'none' : 'inline-flex';
            });
        };

        const calculate = () => {
            let quoteTotal = 0;

            container.querySelectorAll('[data-quote-item]').forEach((item) => {
                const quantity = Number(item.querySelector('[name$="[quantity]"]').value || 0);
                const unitPrice = Number(item.querySelector('[name$="[unit_price]"]').value || 0);
                const subtotal = quantity * unitPrice;

                item.querySelector('[data-item-total]').textContent = money(subtotal);
                quoteTotal += subtotal;
            });

            quoteTotal = Math.max(quoteTotal - Number(discount.value || 0), 0);
            const taxAmount = Math.round(quoteTotal * 0.19);
            net.textContent = money(quoteTotal);
            tax.textContent = money(taxAmount);
            total.textContent = money(quoteTotal + taxAmount);
        };

        addButton.addEventListener('click', () => {
            const index = container.querySelectorAll('[data-quote-item]').length;
            container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
            refreshIndexes();
            calculate();
        });

        container.addEventListener('input', (event) => {
            if (event.target.classList.contains('quote-calc')) {
                calculate();
            }
        });

        container.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-quote-item')) {
                return;
            }

            event.target.closest('[data-quote-item]').remove();
            refreshIndexes();
            calculate();
        });

        discount.addEventListener('input', calculate);
        refreshIndexes();
        calculate();
    })();
</script>
@endsection
