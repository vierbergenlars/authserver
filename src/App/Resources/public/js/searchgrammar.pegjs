// Keep in sync with src/App/Search/SearchGrammar.php
start = Query
Query =
    r:(_? r:Parameter { return r; })* _? {
        return r;
    };
Parameter =
    name:Identifier _? ":" _? value:Str {
        return {
            'name': name,
            'value': value
        };
    };

Identifier =
    s:[a-zA-Z0-9]+ {
        return s.join('');
    };

Str =
    Identifier
    / "'" head:Chars tail:(_ r:Chars { return " ".$r; })* "'" {
        return head+tail.join(' ');
    };

 Chars =
    s:[^']+ {
        return s.join('');
    };
_ = " "+;
