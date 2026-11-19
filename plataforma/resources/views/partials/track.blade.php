@if (\App\Support\Analytics::active())
<script>(window.mcEvents = window.mcEvents || []).push(@json([$event, $params]));</script>
@endif
