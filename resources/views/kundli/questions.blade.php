@foreach ([1, 2] as $n)
<label>Security question {{ $n }}
  <select name="sq{{ $n }}" required>
    <option value="">Choose a question</option>
    @foreach ($questions as $q)<option @selected(old('sq'.$n, $current[$n - 1] ?? null) === $q)>{{ $q }}</option>@endforeach
  </select>
</label>
<label>Answer {{ $n }}<input name="sa{{ $n }}" required minlength="2" maxlength="100" autocomplete="off"></label>
@endforeach
<p class="muted">Answers are not case-sensitive. They let you reset your password without email.</p>
