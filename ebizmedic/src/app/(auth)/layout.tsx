import { Stethoscope } from 'lucide-react';

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen">
      {/* Left panel — branding */}
      <div className="hidden w-1/2 flex-col justify-between bg-primary-700 p-12 text-white lg:flex">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
            <Stethoscope className="h-5 w-5 text-white" />
          </div>
          <span className="text-2xl font-bold">eBizMedic</span>
        </div>

        <div>
          <h2 className="text-4xl font-bold leading-tight">
            Healthcare Infrastructure
            <br />
            for Organisations
          </h2>
          <p className="mt-4 text-lg text-primary-200">
            Connecting 1,630+ doctors, 3,000+ dispensary SKUs, and enterprise organisations
            on a single healthcare operating platform.
          </p>

          <div className="mt-10 grid grid-cols-3 gap-6">
            {[
              { value: '1,630+', label: 'Doctors' },
              { value: '3,000+', label: 'Dispensary SKUs' },
              { value: 'B2B2C', label: 'Platform Model' },
            ].map((stat) => (
              <div key={stat.label}>
                <p className="text-3xl font-bold">{stat.value}</p>
                <p className="text-sm text-primary-200">{stat.label}</p>
              </div>
            ))}
          </div>
        </div>

        <p className="text-sm text-primary-300">© {new Date().getFullYear()} eBizMedic. All rights reserved.</p>
      </div>

      {/* Right panel — form */}
      <div className="flex flex-1 flex-col items-center justify-center p-8">
        <div className="flex w-full max-w-md flex-col">
          <div className="mb-6 flex items-center gap-2 lg:hidden">
            <Stethoscope className="h-6 w-6 text-primary-600" />
            <span className="text-xl font-bold text-gray-900">eBizMedic</span>
          </div>
          {children}
        </div>
      </div>
    </div>
  );
}
