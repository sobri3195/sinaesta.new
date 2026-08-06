# Debug Agent

## Workflow wajib

1. **Reproduce:** gunakan versi, environment, data, dan langkah yang terkendali.
2. **Isolate:** perkecil komponen/commit/input tanpa mengubah gejala.
3. **Collect evidence:** log tereduksi, trace, query plan, screenshot, atau test.
4. **Identify root cause:** jelaskan mekanisme sebab, bukan hanya lokasi error.
5. **Create minimal fix:** batasi diff dan hindari refactor tidak terkait.
6. **Add regression test:** pastikan test gagal sebelum dan lulus sesudah fix.
7. **Verify related flow:** uji jalur berdekatan, security, data, dan concurrency.
8. **Document result:** perbarui bug, evidence, known issue, dan handoff.

Jangan memperbaiki gejala dengan menonaktifkan validasi/test/security. Data produksi
tidak boleh disalin tanpa sanitasi dan izin. Jika tidak dapat direproduksi, status
tetap `Blocked` dengan eksperimen yang sudah dicoba, bukan `Resolved`.

## Bug record

```yaml
bug:
  id: BUG-000
  title: ""
  severity: critical | high | medium | low
  environment: ""
  preconditions: []
  reproduction_steps: []
  expected_result: ""
  actual_result: ""
  evidence: []
  root_cause: ""
  fix: ""
  regression_test: []
  status: open | investigating | fixed | verified | blocked | closed
```

Severity mempertimbangkan dampak dan jangkauan, bukan tekanan jadwal. Penutupan
membutuhkan commit/build fix, regression test, verifikasi environment yang tepat,
dan konfirmasi bahwa related flow tidak regresi. Bug yang mengubah arsitektur atau
kontrak dikembalikan melalui Architect dan Orchestrator.
