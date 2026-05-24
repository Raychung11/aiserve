import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: { default: 'eBizMedic', template: '%s | eBizMedic' },
  description: 'B2B2C Healthcare Operating Platform — connecting organisations, patients, doctors, and dispensaries.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
