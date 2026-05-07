<x-guest-layout>
<style>
  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-gray-100);
    font-family: 'Segoe UI', sans-serif;
    padding: 20px;
  }

  .card-detail {
    background: var(--bs-white);
    border: none;
    border-radius: 18px;
    padding: 40px 30px;
    width: 600px;
    max-width: 90vw;
    box-shadow: 0 4px 32px rgba(0, 0, 0, 0.08);
    text-align: center;
    animation: fadeUp 0.45s ease forwards;
    margin: 0 auto;
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .check-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: var(--bs-success);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    box-shadow: 0 6px 18px rgba(60, 174, 92, 0.3);
    animation: pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s both;
  }

  @keyframes pop {
    from { transform: scale(0.3); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
  }

  h1 {
    font-size: 21px;
    font-weight: 700;
    color: var(--bs-body-color);
    margin-bottom: 12px;
  }

  .subtitle {
    font-size: 14.5px;
    color: var(--bs-secondary-color);
    line-height: 1.7;
    margin-bottom: 28px;
  }

  .footer-note {
    font-size: 13px;
    color: var(--bs-tertiary-color);
    margin-top: 8px;
  }
</style>

<div class="card-detail">
  <div class="check-circle">
    <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
         stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M5 13l4 4L19 7"/>
    </svg>
  </div>

  <h1>{{ __('messages.email_verified_successfully') }}</h1>

  <p class="subtitle">
    {{ __('messages.email_verification_success_message') }}
  </p>
</div>
</x-guest-layout>
