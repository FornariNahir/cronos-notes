@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(config('app.url'), '/') }}/img/logo-cronos.png" class="logo" alt="{{ config('app.name', 'Cronos Notes') }}">
</a>
</td>
</tr>
