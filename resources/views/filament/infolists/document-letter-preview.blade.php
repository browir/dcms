@php
    $record = $getRecord();
    $signaturePath = null;
    if ($record?->signature_id && $record->signature && $record->signature->user_id === $record->created_by) {
        $signaturePath = $record->signature->signature_path;
    }
@endphp

@include('filament.partials.letter-sheet', [
    'content' => $record?->content,
    'title' => $record?->title,
    'code' => $record?->code_number,
    'tanggalSurat' => $record?->tanggal_surat,
    'signerName' => $record?->signer_name,
    'signerNip' => $record?->signer_nip,
    'signerTitle' => $record?->signer_title,
    'hasStamp' => (bool) ($record?->has_stamp),
    'signaturePath' => $signaturePath,
    'downloadPdfUrl' => route('documents.pdf.download', ['document' => $record->id]),
    'streamPdfUrl' => route('documents.pdf.stream', ['document' => $record->id]),
    'downloadWordUrl' => route('documents.word.download', ['document' => $record->id]),
])
