@props(['data'])

{{-- JSON_HEX_TAG keeps a stray </script> inside any value from closing the tag. --}}
<script type="application/ld+json">{!! json_encode($data, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
