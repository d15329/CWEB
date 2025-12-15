<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>C-WEB 仮ログイン</title>
</head>
<body>
    <h1>C-WEB 仮ログイン</h1>
    <form method="POST" action="{{ route('cweb.login.post', ['locale' => request()->route('locale') ?? app()->getLocale()]) }}">
        @csrf
        <label>社員番号:
            <input type="text" name="employee_number" value="{{ old('employee_number') }}">
        </label>
        @error('employee_number')
            <div style="color:red">{{ $message }}</div>
        @enderror
        <button type="submit">ログイン</button>
    </form>
    <p>※将来SSOログインに置き換え</p>
</body>
</html>
