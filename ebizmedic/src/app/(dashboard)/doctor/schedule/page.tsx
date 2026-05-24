'use client';

import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input, Select } from '@/components/ui/input';

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

interface ScheduleEntry {
  dayOfWeek: number;
  startTime: string;
  endTime: string;
  slotDuration: number;
  bufferTime: number;
  isActive: boolean;
}

const SLOT_OPTIONS = [
  { value: '15', label: '15 minutes' },
  { value: '20', label: '20 minutes' },
  { value: '30', label: '30 minutes' },
  { value: '45', label: '45 minutes' },
  { value: '60', label: '1 hour' },
];

const defaultSchedule = (): ScheduleEntry => ({
  dayOfWeek: 1,
  startTime: '09:00',
  endTime: '17:00',
  slotDuration: 30,
  bufferTime: 5,
  isActive: true,
});

export default function DoctorSchedulePage() {
  const [schedules, setSchedules] = useState<ScheduleEntry[]>([]);
  const [doctorId, setDoctorId] = useState('');
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    async function load() {
      const meRes = await fetch('/api/auth/me');
      const me = await meRes.json() as { data: { id: string } };
      const userId = me.data.id;

      const res = await fetch(`/api/doctors/${userId}/schedule`);
      const data = await res.json() as { data: { id: string }[] & ScheduleEntry[] };
      if (data.data?.length) setSchedules(data.data);
    }
    load();
  }, []);

  function addDay() {
    setSchedules((prev) => [...prev, defaultSchedule()]);
  }

  function removeDay(index: number) {
    setSchedules((prev) => prev.filter((_, i) => i !== index));
  }

  function updateDay(index: number, key: keyof ScheduleEntry, value: unknown) {
    setSchedules((prev) =>
      prev.map((s, i) => (i === index ? { ...s, [key]: value } : s))
    );
  }

  async function save() {
    setSaving(true);
    setMessage('');
    try {
      const meRes = await fetch('/api/auth/me');
      const me = await meRes.json() as { data: { id: string } };
      const res = await fetch(`/api/doctors/${me.data.id}/schedule`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ schedules }),
      });
      if (res.ok) setMessage('Schedule saved successfully.');
      else setMessage('Failed to save schedule.');
    } catch {
      setMessage('Network error.');
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-gray-900">My Schedule</h2>
          <p className="text-sm text-gray-500">Configure your availability for appointments</p>
        </div>
        <Button onClick={save} loading={saving}>Save Schedule</Button>
      </div>

      {message && (
        <p className={`rounded-lg px-4 py-2 text-sm ${message.includes('success') ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>
          {message}
        </p>
      )}

      <div className="space-y-4">
        {schedules.map((s, i) => (
          <Card key={i}>
            <CardContent className="pt-6">
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <Select
                  label="Day"
                  value={String(s.dayOfWeek)}
                  options={DAYS.map((d, idx) => ({ value: String(idx), label: d }))}
                  onChange={(e) => updateDay(i, 'dayOfWeek', parseInt(e.target.value))}
                />
                <Input
                  label="Start"
                  type="time"
                  value={s.startTime}
                  onChange={(e) => updateDay(i, 'startTime', e.target.value)}
                />
                <Input
                  label="End"
                  type="time"
                  value={s.endTime}
                  onChange={(e) => updateDay(i, 'endTime', e.target.value)}
                />
                <Select
                  label="Slot Duration"
                  value={String(s.slotDuration)}
                  options={SLOT_OPTIONS}
                  onChange={(e) => updateDay(i, 'slotDuration', parseInt(e.target.value))}
                />
                <Input
                  label="Buffer (min)"
                  type="number"
                  min={0}
                  max={30}
                  value={s.bufferTime}
                  onChange={(e) => updateDay(i, 'bufferTime', parseInt(e.target.value))}
                />
                <div className="flex flex-col gap-1.5">
                  <label className="text-sm font-medium text-gray-700">Active</label>
                  <div className="flex items-center gap-3 pt-1">
                    <input
                      type="checkbox"
                      checked={s.isActive}
                      onChange={(e) => updateDay(i, 'isActive', e.target.checked)}
                      className="h-4 w-4 rounded border-gray-300 text-primary-600"
                    />
                    <Button variant="ghost" size="sm" onClick={() => removeDay(i)} className="text-red-500 hover:text-red-700">
                      Remove
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <Button variant="secondary" onClick={addDay}>
        + Add Day
      </Button>
    </div>
  );
}
