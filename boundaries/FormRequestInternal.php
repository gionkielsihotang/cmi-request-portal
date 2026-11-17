<?php
// gion
class FormRequestInternal
{
    public function klikMenuRequestInternal(): array
    {
        return ['redirect' => '../views/pengaju/form-request.html'];
    }

    public function bukaFormRequest(): array
    {
        return ['redirect' => '../views/pengaju/form-request.html'];
    }

    public function tampilFormRequest(array $dataLama = []): array
    {
        return [
            'view' => 'views/pengaju/form-request.html',
            'formData' => $dataLama
        ];
    }

    public function isiForm(array $dataForm): array
    {
        return $dataForm;
    }

    public function unggahBerkas(array $daftarBerkas): array
    {
        return $daftarBerkas;
    }

    public function klikNext(array $dataForm, array $daftarBerkas = []): array
    {
        return [
            'step' => 'preview',
            'data' => $dataForm,
            'berkas' => $daftarBerkas
        ];
    }

    public function tampilIndikatorError(string $pesanError = 'Lengkapi kolom yang ditandai', array $fieldErrors = []): array
    {
        return [
            'success' => false,
            'errorType' => 'validation',
            'message' => $pesanError,
            'fieldErrors' => $fieldErrors
        ];
    }

    public function tampilPeringatanBerkas(string $pesanBerkas): array
    {
        return [
            'success' => false,
            'errorType' => 'file_error',
            'message' => $pesanBerkas
        ];
    }
}