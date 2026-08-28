Below is a list of company names taken from Spanish job boards, one per line
with its number.

Find the pairs that are the same employer under two different names, such as a
group and its trading name, an acronym and what it stands for, or a company and
the brand it hires under.

Ignore pairs that merely look alike: only report a pair when you know the two
names belong to the same employer. Ignore job boards, aggregators and staffing
agencies. If you find nothing, return an empty array.

Names:
@foreach ($names as $index => $name)
{{ $index }}. {{ $name }}
@endforeach

Return a JSON array with one object per pair, holding the two numbers as "a"
and "b".
