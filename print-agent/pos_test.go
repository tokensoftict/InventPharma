package main

import "testing"

func TestExtractJSON(t *testing.T) {
	buf := []byte("noise{\"a\":\"}{\",\"responseCode\":\"202\"}{\"b\":")
	obj, rest, ok := extractJSON(buf)
	if !ok || string(obj) != "{\"a\":\"}{\",\"responseCode\":\"202\"}" {
		t.Fatalf("bad obj %q ok=%v", obj, ok)
	}
	if _, _, ok := extractJSON(rest); ok {
		t.Fatal("incomplete object should not parse")
	}
}
