package main

import (
	"testing"
	"time"
)

func TestPOSQueueFIFO(t *testing.T) {
	q := &posQueue{}
	a := q.enqueue("purchase", "A", "A", 100)
	b := q.enqueue("purchase", "B", "B", 200)
	c := q.enqueue("purchase", "C", "C", 300)

	if a.Number != 1 || b.Number != 2 || c.Number != 3 {
		t.Fatalf("bad numbers %d %d %d", a.Number, b.Number, c.Number)
	}
	if !q.wait(a, nil) {
		t.Fatal("A should start immediately")
	}
	select {
	case <-b.ready:
		t.Fatal("B must wait for A")
	default:
	}

	// B leaves the queue; C should go next after A.
	if found, active := q.cancelWaiting("B"); !found || active {
		t.Fatal("B should be cancellable")
	}
	if q.wait(b, nil) {
		t.Fatal("cancelled B must not start")
	}
	q.release(a)
	select {
	case <-c.ready:
	case <-time.After(time.Second):
		t.Fatal("C should start after A")
	}
	if snap := q.snapshot(); snap["busy"] != true || snap["total"] != 1 {
		t.Fatalf("bad snapshot %v", snap)
	}
	q.release(c)
	if snap := q.snapshot(); snap["busy"] != false {
		t.Fatal("queue should be idle")
	}
}

func TestPOSQueueClientGivesUp(t *testing.T) {
	q := &posQueue{}
	a := q.enqueue("purchase", "A", "A", 100)
	b := q.enqueue("purchase", "B", "B", 100)
	done := make(chan struct{})
	close(done)
	if q.wait(b, done) {
		t.Fatal("B gave up")
	}
	q.release(a)
	if snap := q.snapshot(); snap["busy"] != false || snap["total"] != 0 {
		t.Fatalf("bad snapshot %v", snap)
	}
}

