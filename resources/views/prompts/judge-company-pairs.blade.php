Below are pairs of company names taken from Spanish job boards. Each name was
typed by whoever published a vacancy, so the same employer often appears under
several spellings.

For every pair, answer whether both names refer to the same employer.

Answer false when:
- they are different divisions or subsidiaries that hire separately
  (Airbus and Airbus Defence and Space)
- one of them is a job board, an aggregator, a staffing portal or a recruiting
  agency rather than the employer itself
- they are unrelated companies whose names happen to look alike
- you are not confident

Answer true only when both names are the same employer, including the case
where one is the short form of the other (Abbott and Abbott Laboratories).

Pairs:
@foreach ($pairs as $index => $pair)
{{ $index }}. "{{ $pair[0] }}" | "{{ $pair[1] }}"
@endforeach

Return a JSON array with one object per pair: the "index" you were given and
"same" as a boolean.
