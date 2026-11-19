<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border-top:1px solid #E5E7EB;font-size:14px;">
@foreach ($order->sellerOrders as $so)
    <tr><td colspan="2" style="padding:10px 0 4px;font-weight:600;">{{ $so->store->name }}</td></tr>
    @foreach ($so->items as $i)
        <tr><td style="padding:2px 0;color:#4A5068;">{{ $i->quantity }} × {{ $i->name }}</td><td align="right" style="padding:2px 0;">{{ cop($i->line_total) }}</td></tr>
    @endforeach
    <tr><td style="padding:2px 0;color:#4A5068;">Envío</td><td align="right" style="padding:2px 0;">{{ $so->shipping_total ? cop($so->shipping_total) : 'Gratis' }}</td></tr>
@endforeach
<tr><td style="padding:10px 0;border-top:1px solid #E5E7EB;font-weight:700;">Total</td><td align="right" style="padding:10px 0;border-top:1px solid #E5E7EB;font-weight:700;">{{ cop($order->total) }}</td></tr>
</table>
