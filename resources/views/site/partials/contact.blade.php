@php
    $formTours = \App\Models\Tour::published()->ordered()->get(['id', 'title', 'start_date']);
@endphp
<section class="section contact-section" id="iletisim">
    <div class="container contact-grid">
        <div class="contact-info">
            <span class="eyebrow">İletişim</span>
            <h2>{{ $settings['contact_title'] }}</h2>
            <p>{{ $settings['contact_text'] }}</p>
            <ul class="contact-list">
                <li><span>🌐</span> {{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'bekirceylan.com.tr' }}</li>
                <li><span>📱</span> <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp: {{ $settings['phone'] }}</a></li>
                <li><span>✉</span> <a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a></li>
                @if($settings['address'])
                    <li><span>📍</span> {{ $settings['address'] }}</li>
                @endif
            </ul>
            @if($settings['map_embed'])
                <div class="map-embed">{!! $settings['map_embed'] !!}</div>
            @endif
        </div>

        <form class="inquiry-form" method="POST" action="{{ route('inquiries.store') }}" data-inquiry-form novalidate>
            @csrf
            <h3>Bilgi Talep Formu</h3>
            @if(session('inquiry_sent'))
                <div class="alert alert-success">
                    Talebiniz alındı. <a href="{{ session('whatsapp_url') }}" target="_blank" rel="noopener">WhatsApp ile göndermek için tıklayın →</a>
                </div>
            @endif
            <div class="alert alert-error" data-form-error hidden></div>

            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

            <label>Ad Soyad *
                <input type="text" name="name" required maxlength="120" value="{{ old('name') }}" autocomplete="name">
            </label>
            <label>Telefon *
                <input type="tel" name="phone" required maxlength="30" value="{{ old('phone') }}" placeholder="05xx xxx xx xx" autocomplete="tel">
            </label>
            <label>İlgilendiğiniz Tur
                <select name="tour_id" data-tour-select>
                    <option value="">Seçiniz</option>
                    @foreach($formTours as $t)
                        <option value="{{ $t->id }}" @selected(old('tour_id', $selectedTourId ?? null) == $t->id)>
                            {{ $t->title }}{{ $t->start_date ? ' – '.$t->start_date->format('d.m.Y') : '' }}
                        </option>
                    @endforeach
                    <option value="" data-interest="Diğer">Diğer / Henüz karar vermedim</option>
                </select>
            </label>
            <label>Mesaj
                <textarea name="message" rows="4" maxlength="2000" placeholder="Kişi sayısı, tarih tercihi vb.">{{ old('message') }}</textarea>
            </label>
            <label class="check">
                <input type="checkbox" name="kvkk" value="1" required>
                <span>Kişisel verilerimin bilgi talebimin yanıtlanması amacıyla işlenmesini kabul ediyorum (KVKK).</span>
            </label>
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            <button type="submit" class="btn btn-whatsapp btn-block">
                @include('site.partials.whatsapp-icon') WhatsApp ile Bilgi Al
            </button>
            <p class="form-note">Talebiniz kaydedilir ve WhatsApp uygulamanız açılır; mesajı göndermeden önce kontrol edebilirsiniz.</p>
        </form>
    </div>
</section>
