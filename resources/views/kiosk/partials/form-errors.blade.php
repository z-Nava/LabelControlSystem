@if($errors->any())
    <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-red-900 shadow-sm" role="alert">
        <div class="font-semibold">No pudimos enviar la requisición</div>
        <p class="mt-1 text-sm">Corrige lo siguiente. La información que ya capturaste permanecerá en el formulario.</p>

        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
