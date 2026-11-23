@php
    $record = $getRecord();
@endphp

@include('filament.partials.letter-sheet', [
    'content' => $record?->content,
    'title' => $record?->title,
    'code' => $record?->code_number,
    'tanggalSurat' => $record?->tanggal_surat,
    'downloadPdfUrl' => route('documents.pdf.download', ['document' => $record->id]),
    'streamPdfUrl' => route('documents.pdf.stream', ['document' => $record->id]),
    'downloadWordUrl' => route('documents.word.download', ['document' => $record->id]),
])
