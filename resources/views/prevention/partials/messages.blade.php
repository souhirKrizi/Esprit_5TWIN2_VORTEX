@if(session('success'))
    <div class="ha-notice ha-notice-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="ha-notice ha-notice-error" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="ha-notice ha-notice-error" role="alert">Le formulaire contient des erreurs. Corrigez les champs en rouge.</div>
@endif
