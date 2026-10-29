import type { CSSProperties, ReactNode } from 'react';
import { Step, Steps } from '@open-slide/core';
import type { DesignSystem, Page, SlideMeta, SlideTransition } from '@open-slide/core';

// ============================================================================
// QUIRE SLATE KEYNOTE — diadaptasi untuk modul Tabel Dinamis (DDA Online)
// Animasi mengikuti keyframe asli deck: qs-rise, qs-fade, qs-draw, qs-fill
// ============================================================================

export const design: DesignSystem = {
  palette: { bg: '#FFFFFF', text: '#1D1D1F', accent: '#0071E3' },
  fonts: {
    display: '"SF Pro Display", -apple-system, BlinkMacSystemFont, "Inter", "Helvetica Neue", sans-serif',
    body: '"SF Pro Text", -apple-system, BlinkMacSystemFont, "Inter", "Helvetica Neue", sans-serif',
  },
  typeScale: { hero: 200, body: 36 },
  radius: 32,
};

// Transisi halaman: fade tipis (sama seperti lapisan ketiga di deck asli).
export const transition: SlideTransition = {
  duration: 420,
  exit: { duration: 320, easing: 'cubic-bezier(0, 0, 0.2, 1)', keyframes: [{ opacity: 1 }, { opacity: 0 }] },
  enter: { duration: 420, easing: 'cubic-bezier(0, 0, 0.2, 1)', keyframes: [{ opacity: 0 }, { opacity: 1 }] },
};

// ----------------------------------------------------------------------------
// Token warna (diambil dari deck asli) + warna aplikasi DDA
// ----------------------------------------------------------------------------
const BG = '#FFFFFF';
const ALT = 'rgb(245, 245, 247)';
const INK = '#1D1D1F';
const MUTED = 'rgb(134, 134, 139)';
const BLUE = '#0071E3';
const TERRA = 'rgb(182, 83, 28)';
const DISP = 'var(--osd-font-display)';
const BODY = 'var(--osd-font-body)';

const APP_ORANGE = '#FF6D1F';
const APP_NAVY = '#0B2E59';
const APP_GREEN = '#059669';
const APP_LINE = '#e2e8f0';
const APP_TEXT = '#1e293b';
const APP_SUB = '#64748b';

// ----------------------------------------------------------------------------
// Keyframe asli deck Quire Slate
// ----------------------------------------------------------------------------
const KEYFRAMES = `
@keyframes qs-fade { 0% { opacity: 0; } 100% { opacity: 1; } }
@keyframes qs-rise { 0% { opacity: 0; transform: translateY(28px); filter: blur(10px); } 100% { opacity: 1; transform: none; filter: blur(0px); } }
@keyframes qs-draw { 0% { stroke-dashoffset: 1; } 100% { stroke-dashoffset: 0; } }
@keyframes qs-fill { 0% { transform: scaleX(0); } 100% { transform: scaleX(1); } }
@keyframes qs-loupe { 0% { opacity: 0; transform: scale(0.94); } 100% { opacity: 1; transform: scale(1); } }
@media (prefers-reduced-motion: reduce) { [data-qs] { animation: none !important; } }
`;

const EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';

/** Elemen naik + blur-in dengan jeda bertahap (stagger). */
const rise = (delay = 0, dur = 900): CSSProperties => ({
  animation: `qs-rise ${dur}ms ${EASE} ${delay}ms both`,
});
const fade = (delay = 0, dur = 700): CSSProperties => ({
  animation: `qs-fade ${dur}ms cubic-bezier(0, 0, 0.2, 1) ${delay}ms both`,
});
const loupe = (delay = 0): CSSProperties => ({
  animation: `qs-loupe 700ms ${EASE} ${delay}ms both`,
});
const fillX = (delay = 0, dur = 1100): CSSProperties => ({
  transformOrigin: 'left center',
  animation: `qs-fill ${dur}ms ${EASE} ${delay}ms both`,
});

const Stage = ({ bg = BG, children }: { bg?: string; children: ReactNode }) => (
  <div
    data-qs
    style={{
      width: '100%',
      height: '100%',
      position: 'relative',
      overflow: 'hidden',
      isolation: 'isolate',
      background: bg,
      color: INK,
      fontFamily: BODY,
      WebkitFontSmoothing: 'antialiased',
    }}
  >
    <style>{KEYFRAMES}</style>
    {children}
  </div>
);

const gradientText: CSSProperties = {
  fontFamily: DISP,
  fontWeight: 600,
  letterSpacing: '-0.035em',
  lineHeight: 1.05,
  margin: 0,
  color: 'transparent',
  backgroundImage: 'linear-gradient(rgb(29, 29, 31) 18%, rgb(94, 94, 99) 62%, rgb(167, 167, 173) 100%)',
  WebkitBackgroundClip: 'text',
  backgroundClip: 'text',
  paddingBottom: '0.06em',
};

const Eyebrow = ({ children, style }: { children: ReactNode; style?: CSSProperties }) => (
  <p style={{ fontFamily: DISP, fontSize: 28, fontWeight: 600, letterSpacing: '-0.005em', color: TERRA, margin: 0, ...style }}>
    {children}
  </p>
);

const Headline = ({ size, children, style }: { size: number; children: ReactNode; style?: CSSProperties }) => (
  <h2
    style={{
      fontFamily: DISP,
      fontWeight: 600,
      letterSpacing: '-0.025em',
      lineHeight: 1.05,
      margin: 0,
      color: INK,
      fontSize: size,
      ...style,
    }}
  >
    {children}
  </h2>
);

const Lead = ({ children, style }: { children: ReactNode; style?: CSSProperties }) => (
  <p
    style={{
      fontFamily: BODY,
      fontSize: 34,
      fontWeight: 500,
      lineHeight: 1.45,
      letterSpacing: '-0.012em',
      color: MUTED,
      margin: 0,
      ...style,
    }}
  >
    {children}
  </p>
);

const Strong = ({ children }: { children: ReactNode }) => <span style={{ color: INK }}>{children}</span>;

// ----------------------------------------------------------------------------
// Tablet Slate (bezel metalik + noise, seperti deck asli)
// ----------------------------------------------------------------------------
const NOISE = `url("data:image/svg+xml,${encodeURIComponent(
  "<svg xmlns='http://www.w3.org/2000/svg' width='360' height='360'><filter id='n' x='0' y='0' width='100%' height='100%'><feTurbulence type='fractalNoise' baseFrequency='0.012 0.9' numOctaves='3' stitchTiles='stitch'/><feColorMatrix type='matrix' values='0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 1 0 0 0 0'/></filter><rect width='360' height='360' filter='url(#n)'/></svg>",
)}")`;

const Tablet = ({
  left,
  top,
  width,
  height,
  delay = 0,
  screenBg = '#f4f6f9',
  children,
}: {
  left: number;
  top: number;
  width: number;
  height: number;
  delay?: number;
  screenBg?: string;
  children: ReactNode;
}) => {
  const ix = width * 0.0715;
  const iy = height * 0.0785;
  return (
    <div style={{ position: 'absolute', left, top, width, height, ...rise(delay, 1100) }}>
      <div
        style={{
          position: 'absolute',
          inset: 0,
          borderRadius: 42.3,
          boxShadow:
            'rgba(0,0,0,0.12) 0px 1.41px 2.82px, rgba(0,0,0,0.07) 0px 14.1px 37.6px, rgba(0,0,0,0.11) 0px 65.8px 159.8px',
        }}
      />
      <div
        style={{
          position: 'absolute',
          inset: 0,
          borderRadius: 42.3,
          background:
            'linear-gradient(135deg, rgb(164,165,169) 0%, rgb(74,75,79) 14%, rgb(119,120,124) 34%, rgb(45,46,49) 58%, rgb(91,92,96) 80%, rgb(42,43,46) 100%)',
          overflow: 'hidden',
        }}
      >
        <div style={{ position: 'absolute', inset: 0, backgroundImage: NOISE, backgroundSize: '360px 360px', opacity: 0.22, mixBlendMode: 'overlay' }} />
      </div>
      <div
        style={{
          position: 'absolute',
          inset: 3.525,
          borderRadius: 38.775,
          background: 'rgb(30,31,33)',
          overflow: 'hidden',
          boxShadow: 'rgba(0,0,0,0.28) 0px 0px 0px 0.846px inset',
        }}
      >
        <div
          style={{
            position: 'absolute',
            left: ix - 3.5,
            top: iy - 3.5,
            right: ix - 3.5,
            bottom: iy - 3.5,
            borderRadius: 3,
            background: screenBg,
            overflow: 'hidden',
            padding: '26px 30px',
            boxSizing: 'border-box',
            display: 'flex',
            flexDirection: 'column',
            color: APP_TEXT,
            fontFamily: '"Inter", -apple-system, "Segoe UI", sans-serif',
            letterSpacing: 0,
          }}
        >
          {children}
        </div>
      </div>
    </div>
  );
};

// ----------------------------------------------------------------------------
// Pena (stylus) — elemen khas deck asli
// ----------------------------------------------------------------------------
const Pencil = ({ width = 760, id = 'pc' }: { width?: number; id?: string }) => (
  <svg width={width} height={(width * 86) / 1620} viewBox="0 0 1620 86" aria-hidden="true" style={{ display: 'block', overflow: 'visible' }}>
    <defs>
      <linearGradient id={`${id}b`} x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stopColor="#5C5D61" /><stop offset="0.06" stopColor="#8D8E92" /><stop offset="0.235" stopColor="#8D8E92" />
        <stop offset="0.25" stopColor="#3A3B3E" /><stop offset="0.3" stopColor="#8D8E92" /><stop offset="0.52" stopColor="#5C5D61" />
        <stop offset="0.78" stopColor="#3A3B3E" /><stop offset="0.92" stopColor="#1B1C1E" /><stop offset="1" stopColor="#525357" />
      </linearGradient>
      <linearGradient id={`${id}e`} x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stopColor="#45464A" /><stop offset="0.3" stopColor="#45464A" /><stop offset="0.75" stopColor="#1E1F21" /><stop offset="1" stopColor="#45464A" />
      </linearGradient>
      <linearGradient id={`${id}m`} x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stopColor="#E9E9EB" /><stop offset="0.22" stopColor="#FFFFFF" /><stop offset="0.4" stopColor="#A9A9AD" />
        <stop offset="0.62" stopColor="#E4E4E6" /><stop offset="0.86" stopColor="#6E6E72" /><stop offset="1" stopColor="#B4B4B8" />
      </linearGradient>
      <linearGradient id={`${id}n`} x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stopColor="#F4F4F5" /><stop offset="0.4" stopColor="#D6D6D9" /><stop offset="1" stopColor="#8E8E93" />
      </linearGradient>
    </defs>
    <path d="M152 0 H40 C15 0 0 19 0 43 C0 67 15 86 40 86 H152 Z" fill={`url(#${id}e)`} />
    <rect x="150" y="0" width="1152" height="86" fill={`url(#${id}b)`} />
    <path d="M1338 0 C1426 9 1508 26 1566 34 L1566 52 C1508 60 1426 77 1338 86 Z" fill={`url(#${id}b)`} />
    <path d="M1564 34 C1590 37 1610 40 1619 43 C1610 46 1590 49 1564 52 Z" fill={`url(#${id}n)`} />
    <rect x="1300" y="0" width="40" height="86" fill={`url(#${id}m)`} />
  </svg>
);

// ----------------------------------------------------------------------------
// Potongan UI aplikasi DDA (replika dari view PHP asli)
// ----------------------------------------------------------------------------
const AppBtn = ({ children, bg, color = '#fff', outline }: { children: ReactNode; bg?: string; color?: string; outline?: string }) => (
  <span
    style={{
      display: 'inline-flex',
      alignItems: 'center',
      padding: '9px 18px',
      borderRadius: 8,
      fontSize: 18,
      fontWeight: 700,
      background: bg ?? 'transparent',
      color,
      border: outline ? `1.5px solid ${outline}` : 'none',
      whiteSpace: 'nowrap',
    }}
  >
    {children}
  </span>
);

const th: CSSProperties = { padding: '10px 12px', borderRight: `1px solid ${APP_LINE}`, borderBottom: `1px solid ${APP_LINE}`, fontWeight: 700 };
const td: CSSProperties = { padding: '10px 12px', borderRight: `1px solid ${APP_LINE}`, borderBottom: `1px solid ${APP_LINE}` };
const num: CSSProperties = { ...td, textAlign: 'right', fontVariantNumeric: 'tabular-nums' };

const MatrixTable = ({ rows, fs = 20 }: { rows: { n: string; v: string[] }[]; fs?: number }) => (
  <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: fs, background: '#fff' }}>
    <thead>
      <tr style={{ background: '#eef2f7' }}>
        <th rowSpan={3} style={{ ...th, textAlign: 'left', width: '34%' }}>Kabupaten/Kota</th>
        <th colSpan={4} style={{ ...th, textAlign: 'center', color: '#c2410c', background: '#fff7ed' }}>Jumlah Penduduk</th>
      </tr>
      <tr style={{ background: '#f1f5f9' }}>
        <th colSpan={2} style={{ ...th, textAlign: 'center', color: '#15803d', background: '#f0fdf4' }}>2023</th>
        <th colSpan={2} style={{ ...th, textAlign: 'center', color: '#15803d', background: '#f0fdf4' }}>2024</th>
      </tr>
      <tr style={{ background: '#f8fafc', fontSize: fs - 2 }}>
        {['Laki-laki', 'Perempuan', 'Laki-laki', 'Perempuan'].map((h, i) => (
          <th key={i} style={{ ...th, textAlign: 'center', color: APP_SUB }}>{h}</th>
        ))}
      </tr>
    </thead>
    <tbody>
      {rows.map((r, i) => (
        <tr key={i}>
          <td style={{ ...td, fontWeight: 600 }}>{r.n}</td>
          {r.v.map((x, j) => (
            <td key={j} style={num}>{x}</td>
          ))}
        </tr>
      ))}
    </tbody>
  </table>
);

const ROWS = [
  { n: 'Cilacap', v: ['1.002.314', '989.412', '1.010.220', '995.870'] },
  { n: 'Banyumas', v: ['912.440', '908.112', '918.305', '913.640'] },
  { n: 'Purbalingga', v: ['516.320', '512.980', '519.870', '516.115'] },
  { n: 'Kota Semarang', v: ['831.200', '849.520', '836.940', '855.310'] },
];

// ============================================================================
// SLIDE 1 — COVER
// ============================================================================
const Cover: Page = () => (
  <Stage>
    <div style={{ position: 'absolute', left: 0, top: 96, width: 1920, display: 'flex', justifyContent: 'center', ...rise(0) }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 11 }}>
        <svg width="26.5" height="34" viewBox="0 0 28 36" aria-hidden="true">
          <path d="M2 4 C2 2.9 2.9 2 4 2 H18 L26 10 V32 C26 33.1 25.1 34 24 34 H4 C2.9 34 2 33.1 2 32 Z" fill="#1D1D1F" />
          <path d="M18 2 V8 C18 9.1 18.9 10 20 10 H26" fill="none" stroke="#FFFFFF" strokeWidth="1.6" />
        </svg>
        <span style={{ fontFamily: DISP, fontSize: 34, fontWeight: 600, letterSpacing: '-0.02em', lineHeight: 1 }}>DDA Online</span>
      </div>
    </div>

    <h1 style={{ ...gradientText, position: 'absolute', left: 0, top: 160, width: 1920, textAlign: 'center', fontSize: 190, ...rise(140) }}>
      Tabel Dinamis
    </h1>

    <Tablet left={382} top={500} width={1156} height={883} delay={420}>
      <div style={{ display: 'flex', gap: 12, marginBottom: 18 }}>
        <AppBtn bg="#fff" color={APP_SUB} outline="#cbd5e1">‹ Kembali</AppBtn>
        <AppBtn bg="#fff" color={APP_SUB} outline="#cbd5e1">Konfigurasi</AppBtn>
        <span style={{ flex: 1 }} />
        <AppBtn bg={APP_GREEN}>UPLOAD EXCEL</AppBtn>
        <AppBtn bg={BLUE}>UNDUH TEMPLATE</AppBtn>
      </div>
      <div style={{ fontSize: 26, fontWeight: 800, color: APP_NAVY, marginBottom: 14 }}>
        Jumlah Penduduk Menurut Kabupaten/Kota dan Jenis Kelamin
      </div>
      <div style={{ border: `1px solid ${APP_LINE}`, borderRadius: 8, overflow: 'hidden' }}>
        <MatrixTable rows={ROWS} fs={21} />
      </div>
      <div style={{ marginTop: 14, fontSize: 18, color: APP_SUB }}><b>Sumber/</b><i>Source</i>: BPS Provinsi Jawa Tengah</div>
    </Tablet>

    <div style={{ position: 'absolute', left: 520, top: 462, filter: 'drop-shadow(rgba(0,0,0,0.22) 0px 4.85px 6.06px)', ...rise(900) }}>
      <Pencil width={760} id="cv" />
    </div>
  </Stage>
);

// ============================================================================
// SLIDE 2 — PERNYATAAN (Steps: kalimat muncul per klik)
// ============================================================================
const Statement: Page = () => (
  <Stage bg={ALT}>
    <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
      <Headline size={120} style={{ textAlign: 'center', ...rise(0, 1000) }}>
        Tabel itu{' '}
        <span style={{ position: 'relative', display: 'inline-block' }}>
          dirakit.
          <svg width="420" height="44" viewBox="0 0 420 44" preserveAspectRatio="none" aria-hidden="true" style={{ position: 'absolute', left: -4, top: 118, overflow: 'visible' }}>
            <path
              d="M6 30 C90 18 210 10 330 14 C362 15 392 18 412 22"
              pathLength={1}
              style={{ fill: 'none', stroke: TERRA, strokeWidth: 7, strokeLinecap: 'round', strokeDasharray: 1, animation: 'qs-draw 900ms cubic-bezier(0.4,0,0.2,1) 900ms both' }}
            />
          </svg>
        </span>
      </Headline>
      <div style={{ marginTop: 92, width: 1340, textAlign: 'center' }}>
        <Steps>
          <Step duration={420}>
            <Lead style={{ fontSize: 38 }}>
              Mengetik ulang angka ke tabel yang sama setiap tahun itu lambat dan rawan salah. <Strong>Sistem ini memisahkan struktur dari isi.</Strong>
            </Lead>
          </Step>
          <Step duration={320}>
            <Lead style={{ fontSize: 38, marginTop: 28 }}>
              Baris, kolom, dan periode disusun sekali. <Strong>Angkanya diisi lewat satu berkas Excel.</Strong>
            </Lead>
          </Step>
        </Steps>
      </div>
    </div>
  </Stage>
);

// ============================================================================
// SLIDE 3 — ARSITEKTUR DATA (tablet + penanda Baris / Kolom / Periode)
// ============================================================================
const Tag = ({ color, label, sub, style }: { color: string; label: string; sub: string; style: CSSProperties }) => (
  <div style={{ position: 'absolute', width: 300, ...style }}>
    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
      <span style={{ width: 16, height: 16, borderRadius: 8, background: color }} />
      <span style={{ fontFamily: DISP, fontSize: 38, fontWeight: 600, letterSpacing: '-0.02em' }}>{label}</span>
    </div>
    <div style={{ fontFamily: BODY, fontSize: 22, fontWeight: 500, color: MUTED, marginTop: 6, lineHeight: 1.35 }}>{sub}</div>
  </div>
);

const Leader = ({ d, delay }: { d: string; delay: number }) => (
  <path
    d={d}
    pathLength={1}
    style={{ fill: 'none', stroke: 'rgba(29,29,31,0.45)', strokeWidth: 2.4, strokeLinecap: 'round', strokeDasharray: 1, animation: `qs-draw 800ms cubic-bezier(0.4,0,0.2,1) ${delay}ms both` }}
  />
);

const Arsitektur: Page = () => (
  <Stage>
    <div style={{ position: 'absolute', left: 0, top: 84, width: 1920, display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
      <Eyebrow style={rise(0)}>Arsitektur Data</Eyebrow>
      <Headline size={116} style={{ marginTop: 14, ...rise(120) }}>Tiga dimensi. Satu tabel.</Headline>
      <Lead style={{ marginTop: 18, color: INK, fontSize: 36, ...rise(240) }}>Setiap angka punya tiga koordinat: baris, kolom, dan periode.</Lead>
    </div>

    <Tablet left={470} top={440} width={980} height={760} delay={420}>
      <div style={{ fontSize: 24, fontWeight: 800, color: APP_NAVY, marginBottom: 14 }}>Jumlah Penduduk Menurut Kabupaten/Kota</div>
      <div style={{ border: `1px solid ${APP_LINE}`, borderRadius: 8, overflow: 'hidden' }}>
        <MatrixTable rows={ROWS} fs={20} />
      </div>
    </Tablet>

    <svg width="1920" height="1080" viewBox="0 0 1920 1080" style={{ position: 'absolute', inset: 0, pointerEvents: 'none', overflow: 'visible' }}>
      <Leader d="M 410 760 C 450 760 480 760 560 760" delay={1300} />
      <Leader d="M 410 600 C 460 600 500 604 640 604" delay={1700} />
      <Leader d="M 1510 650 C 1470 650 1440 650 1380 650" delay={2100} />
    </svg>
    <div style={fade(1100)}>
      <Tag color="#2563EB" label="Baris" sub="Wilayah atau kategori yang diamati" style={{ left: 100, top: 724 }} />
    </div>
    <div style={fade(1500)}>
      <Tag color="#EA580C" label="Kolom" sub="Variabel, bisa bertingkat" style={{ left: 100, top: 566 }} />
    </div>
    <div style={fade(1900)}>
      <Tag color="#16A34A" label="Periode" sub="Tahun atau rentang waktu" style={{ left: 1530, top: 614 }} />
    </div>
  </Stage>
);

// ============================================================================
// SLIDE 4 — MASTER DIMENSI
// ============================================================================
const MasterDimensi: Page = () => (
  <Stage>
    <div style={{ position: 'absolute', left: 130, top: 190, width: 720 }}>
      <Eyebrow style={rise(0)}>Master Dimensi</Eyebrow>
      <Headline size={86} style={{ marginTop: 20, ...rise(120) }}>Susun sekali.<br />Pakai berulang.</Headline>
      <Lead style={{ marginTop: 36, ...rise(260) }}>
        Kelompok karakteristik, baris, dan periode disimpan di satu tempat. <Strong>Tabel baru tinggal memilihnya.</Strong>
      </Lead>
      <div style={{ display: 'flex', alignItems: 'baseline', gap: 14, marginTop: 56, fontFamily: DISP, fontWeight: 600, letterSpacing: '-0.03em', lineHeight: 1, ...rise(420) }}>
        <span style={{ fontSize: 128 }}>3</span>
        <span style={{ fontSize: 38, letterSpacing: '-0.01em', color: MUTED }}>master</span>
      </div>
      <div style={{ fontSize: 24, color: MUTED, marginTop: 10, ...rise(520) }}>Karakteristik, Baris, dan Periode</div>
    </div>

    <Tablet left={930} top={150} width={900} height={780} delay={300}>
      <div style={{ display: 'flex', gap: 10, marginBottom: 20 }}>
        <AppBtn bg={APP_ORANGE}>Karakteristik Kolom</AppBtn>
        <AppBtn bg="#fff" color={APP_SUB} outline="#cbd5e1">Baris</AppBtn>
        <AppBtn bg="#fff" color={APP_SUB} outline="#cbd5e1">Periode</AppBtn>
      </div>
      <div style={{ fontSize: 24, fontWeight: 800, color: APP_TEXT, marginBottom: 14 }}>Daftar Kelompok Karakteristik (Dimensi Kolom)</div>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 19, background: '#fff', border: `1px solid ${APP_LINE}` }}>
        <thead>
          <tr style={{ background: '#eef2f7' }}>
            <th style={{ ...th, width: '9%', textAlign: 'center' }}>No.</th>
            <th style={{ ...th, textAlign: 'left' }}>Nama Kelompok (Indonesia &amp; English)</th>
            <th style={{ ...th, width: '17%', textAlign: 'center' }}>Jumlah Kolom</th>
            <th style={{ ...th, width: '27%', textAlign: 'center', borderRight: 'none' }}>Aksi</th>
          </tr>
        </thead>
        <tbody>
          {[
            ['1', 'Jenis Kelamin', 'Sex', '3'],
            ['2', 'Kelompok Umur', 'Age Group', '7'],
            ['3', 'Lapangan Usaha', 'Industry', '9'],
            ['4', 'Status Pekerjaan', 'Employment Status', '5'],
          ].map((r, i) => (
            <tr key={i} style={{ ...fade(700 + i * 160) }}>
              <td style={{ ...td, textAlign: 'center', color: APP_SUB }}>{r[0]}</td>
              <td style={td}>
                <div style={{ fontWeight: 700 }}>{r[1]}</div>
                <div style={{ fontSize: 16, color: APP_SUB, fontStyle: 'italic' }}>{r[2]}</div>
              </td>
              <td style={{ ...td, textAlign: 'center', fontWeight: 700 }}>{r[3]}</td>
              <td style={{ ...td, textAlign: 'center', borderRight: 'none' }}>
                <span style={{ display: 'inline-flex', gap: 8 }}>
                  <span style={{ padding: '5px 10px', borderRadius: 8, border: `1px solid ${BLUE}`, color: BLUE, fontSize: 16, fontWeight: 600 }}>Kelola Kolom</span>
                  <span style={{ padding: '5px 10px', borderRadius: 8, border: '1px solid #cbd5e1', color: APP_SUB, fontSize: 16, fontWeight: 600 }}>Edit</span>
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </Tablet>
  </Stage>
);

// ============================================================================
// SLIDE 5 — HIERARKI KOLOM (angka besar + pohon bertingkat)
// ============================================================================
const TREE: { lv: number; t: string; role: string; c: string; bg: string }[] = [
  { lv: 0, t: 'Jumlah Penduduk', role: 'Kategori', c: '#b45309', bg: '#fef3c7' },
  { lv: 1, t: 'Laki-laki', role: 'Kategori', c: '#b45309', bg: '#fef3c7' },
  { lv: 2, t: '0–14 tahun', role: 'Data', c: '#1d4ed8', bg: '#dbeafe' },
  { lv: 2, t: '15–64 tahun', role: 'Data', c: '#1d4ed8', bg: '#dbeafe' },
  { lv: 1, t: 'Perempuan', role: 'Data', c: '#1d4ed8', bg: '#dbeafe' },
  { lv: 1, t: 'Jumlah', role: 'Total', c: '#15803d', bg: '#dcfce7' },
];

const Hierarki: Page = () => (
  <Stage bg={ALT}>
    <div style={{ position: 'absolute', left: 200, top: 110 }}>
      <Eyebrow style={rise(0)}>Hierarki Kolom</Eyebrow>
      <h2 style={{ ...gradientText, fontSize: 210, marginTop: 6, ...rise(120, 1000) }}>3 level</h2>
    </div>
    <Lead style={{ position: 'absolute', left: 1000, top: 190, width: 720, ...rise(300) }}>
      <Strong>Header kolom bisa bertingkat.</Strong> Pohon disusun di Tree Builder, lalu tabel menghitung gabungan selnya sendiri.
    </Lead>

    <div style={{ position: 'absolute', left: 200, top: 520, width: 1520, height: 440, borderRadius: 32, background: '#fff', boxShadow: 'rgba(0,0,0,0.06) 0px 10px 30px', padding: '28px 40px', boxSizing: 'border-box', ...rise(480) }}>
      {TREE.map((r, i) => (
        <div
          key={i}
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginLeft: r.lv * 64,
            marginBottom: 8,
            padding: '8px 18px',
            borderRadius: 10,
            border: `1px solid ${APP_LINE}`,
            borderLeft: `6px solid ${r.lv === 0 ? APP_ORANGE : r.lv === 1 ? '#0ea5e9' : '#10b981'}`,
            ...rise(700 + i * 140, 800),
          }}
        >
          <span style={{ fontSize: 26, fontWeight: 700, color: APP_TEXT }}>{r.t}</span>
          <span style={{ fontSize: 20, fontWeight: 700, padding: '3px 14px', borderRadius: 999, color: r.c, background: r.bg }}>{r.role}</span>
        </div>
      ))}
    </div>
  </Stage>
);

// ============================================================================
// SLIDE 6 — KELOMPOK BARIS (replika modal "Tambah Kelompok Baris (Bertingkat)")
// ============================================================================
const Field = ({ label, value, hint, ph }: { label: string; value?: string; hint?: string; ph?: boolean }) => (
  <div style={{ flex: 1 }}>
    <div style={{ fontSize: 16, fontWeight: 800, marginBottom: 6 }}>{label}</div>
    <div style={{ padding: '9px 12px', border: '1px solid #cbd5e1', borderRadius: 8, background: '#fff', fontSize: 18, color: ph ? '#94a3b8' : APP_TEXT, fontWeight: ph ? 400 : 600 }}>
      {value}
    </div>
    {hint && <div style={{ fontSize: 14, color: APP_SUB, marginTop: 5, lineHeight: 1.3 }}>{hint}</div>}
  </div>
);

const ModalCard = ({ title, children }: { title: string; children: ReactNode }) => (
  <div style={{ background: '#fff', borderRadius: 14, border: `1px solid ${APP_LINE}`, padding: '22px 26px', display: 'flex', flexDirection: 'column', flex: 1, boxShadow: '0 12px 30px rgba(15,23,42,0.08)' }}>
    <div style={{ fontSize: 25, fontWeight: 800, color: APP_TEXT, marginBottom: 16 }}>{title}</div>
    {children}
  </div>
);

const ModalFooter = ({ save }: { save: string }) => (
  <div style={{ marginTop: 'auto', display: 'flex', justifyContent: 'flex-end', gap: 10, paddingTop: 12 }}>
    <AppBtn bg="#64748b">Batal</AppBtn>
    <AppBtn bg={APP_ORANGE}>{save}</AppBtn>
  </div>
);

const ItemRow = ({ prefix, text, lv = 0 }: { prefix?: string; text: string; lv?: number }) => (
  <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginLeft: lv * 40, marginBottom: 8, padding: '8px 14px', border: `1px solid ${APP_LINE}`, borderLeft: `5px solid ${lv === 0 ? APP_ORANGE : '#0ea5e9'}`, borderRadius: 8, fontSize: 19, fontWeight: 600 }}>
    {prefix && <span style={{ color: APP_ORANGE, fontWeight: 800, minWidth: 30 }}>{prefix}</span>}
    <span>{text}</span>
  </div>
);

const Baris: Page = () => (
  <Stage>
    <div style={{ position: 'absolute', left: 130, top: 190, width: 720 }}>
      <Eyebrow style={rise(0)}>Kelompok Baris</Eyebrow>
      <Headline size={86} style={{ marginTop: 20, ...rise(120) }}>Buat baris.<br />Bertingkat.</Headline>
      <Lead style={{ marginTop: 36, ...rise(260) }}>
        Isi nama kelompok dan judul header, tambahkan item baris, lalu <Strong>nomori otomatis dengan Generate.</Strong>
      </Lead>
      <div style={{ display: 'flex', alignItems: 'baseline', gap: 14, marginTop: 52, fontFamily: DISP, fontWeight: 600, letterSpacing: '-0.03em', lineHeight: 1, ...rise(420) }}>
        <span style={{ fontSize: 128 }}>3</span>
        <span style={{ fontSize: 38, letterSpacing: '-0.01em', color: MUTED }}>level penomoran</span>
      </div>
      <p style={{ fontFamily: BODY, fontSize: 26, fontWeight: 600, color: BLUE, margin: '30px 0 0 0', ...rise(560) }}>Klik untuk melihat langkahnya ›</p>
    </div>

    <Tablet left={930} top={110} width={900} height={860} delay={300}>
      <ModalCard title="Tambah Kelompok Baris (Bertingkat)">
        <div style={{ display: 'flex', gap: 14, marginBottom: 12, ...fade(700) }}>
          <Field label="Nama Kelompok (Bahasa Indonesia) *" value="Lapangan Usaha (17 Sektor)" hint="Nama unik untuk pilihan dropdown generator tabel." />
          <Field label="Judul Header Tabel Baris *" value="Lapangan Usaha" hint="Judul di kepala baris tabel publikasi & Excel." />
        </div>
        <div style={{ marginBottom: 14, ...fade(900) }}>
          <Field label="Judul Sub-Baris (opsional)" value="Provinsi" ph hint="Kosongkan jika baris biasa (1 kolom). Isi jika baris bertingkat 2 kolom." />
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', margin: '4px 0 8px' }}>
          <span style={{ fontSize: 18, fontWeight: 800 }}>Daftar Item Baris</span>
          <span style={{ fontSize: 15, fontWeight: 700, color: '#1d4ed8', background: '#dbeafe', borderRadius: 999, padding: '2px 12px' }}>3 Baris</span>
        </div>
        <Steps>
          <Step duration={420}>
            <ItemRow text="Pertanian, Kehutanan, dan Perikanan" />
            <ItemRow text="Pertambangan dan Penggalian" />
            <ItemRow text="Industri Pengolahan" />
          </Step>
          <Step duration={320}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '10px 12px', background: '#fff7ed', border: `1px solid #fed7aa`, borderRadius: 10, fontSize: 16, marginBottom: 8 }}>
              <b>Level 1</b>
              <span style={{ background: '#fff', border: '1px solid #cbd5e1', borderRadius: 6, padding: '3px 10px' }}>Huruf (A, B, C...)</span>
              <b>Level 2</b>
              <span style={{ background: '#fff', border: '1px solid #cbd5e1', borderRadius: 6, padding: '3px 10px' }}>Angka (1, 2, 3...)</span>
              <span style={{ marginLeft: 'auto', background: APP_ORANGE, color: '#fff', fontWeight: 700, borderRadius: 8, padding: '5px 14px' }}>Generate</span>
            </div>
          </Step>
          <Step duration={320}>
            <ItemRow prefix="A." text="Pertanian, Kehutanan, dan Perikanan" />
            <ItemRow prefix="B." text="Pertambangan dan Penggalian" />
            <ItemRow prefix="C." text="Industri Pengolahan" />
          </Step>
        </Steps>
        <ModalFooter save="Simpan Kelompok Baris" />
      </ModalCard>
    </Tablet>
  </Stage>
);

// ============================================================================
// SLIDE 7 — PERIODE WAKTU (replika modal "Tambah Kelompok Periode")
// ============================================================================
const Periode: Page = () => (
  <Stage bg={ALT}>
    <div style={{ position: 'absolute', left: 130, top: 190, width: 720 }}>
      <Eyebrow style={rise(0)}>Periode Waktu</Eyebrow>
      <Headline size={86} style={{ marginTop: 20, ...rise(120) }}>Atur waktu.<br />Lihat posisinya.</Headline>
      <Lead style={{ marginTop: 36, ...rise(260) }}>
        Tentukan nama kelompok dan judul header, lalu tambahkan periode. <Strong>Live Preview menampilkan posisi kolom waktunya</Strong> di tabel.
      </Lead>
      <div style={{ display: 'flex', alignItems: 'baseline', gap: 14, marginTop: 52, fontFamily: DISP, fontWeight: 600, letterSpacing: '-0.03em', lineHeight: 1, ...rise(420) }}>
        <span style={{ fontSize: 128 }}>4</span>
        <span style={{ fontSize: 38, letterSpacing: '-0.01em', color: MUTED }}>jenis periode</span>
      </div>
      <div style={{ fontSize: 24, color: MUTED, marginTop: 10, ...rise(520) }}>Tahunan, Triwulan, Bulanan, Semester</div>
      <p style={{ fontFamily: BODY, fontSize: 26, fontWeight: 600, color: BLUE, margin: '30px 0 0 0', ...rise(620) }}>Klik untuk melihat langkahnya ›</p>
    </div>

    <Tablet left={930} top={110} width={900} height={860} delay={300}>
      <ModalCard title="Tambah Kelompok Periode (Dimensi Waktu)">
        <div style={{ display: 'flex', gap: 14, marginBottom: 14, ...fade(700) }}>
          <Field label="Nama Kelompok (Bahasa Indonesia) *" value="Tahunan 2021-2025" hint="Nama untuk pilihan dropdown di aplikasi." />
          <Field label="Judul Kolom Header (ID) *" value="Tahun" hint="Teks header tabel jika periode dijadikan kolom/baris." />
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', margin: '4px 0 8px' }}>
          <span style={{ fontSize: 18, fontWeight: 800 }}>Daftar Periode</span>
          <span style={{ fontSize: 15, fontWeight: 700, color: '#15803d', background: '#dcfce7', borderRadius: 999, padding: '2px 12px' }}>5 Periode</span>
        </div>
        <Steps>
          <Step duration={420}>
            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', marginBottom: 10 }}>
              {['2021', '2022', '2023', '2024', '2025'].map((y) => (
                <span key={y} style={{ padding: '8px 20px', border: `1px solid ${APP_LINE}`, borderLeft: '5px solid #16A34A', borderRadius: 8, fontSize: 20, fontWeight: 700 }}>{y}</span>
              ))}
              <span style={{ padding: '8px 16px', border: '1.5px solid #059669', color: '#059669', borderRadius: 8, fontSize: 17, fontWeight: 700 }}>+ Tambah Periode Baru</span>
            </div>
          </Step>
          <Step duration={320}>
            <div style={{ border: `1px solid ${APP_LINE}`, borderRadius: 10, padding: 14, background: '#f8fafc' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 }}>
                <span style={{ fontSize: 14, color: APP_SUB }}>Simulasi posisi kolom periode waktu pada tabel seperti dalam publikasi BPS</span>
                <span style={{ fontSize: 14, fontWeight: 700, color: '#15803d', background: '#dcfce7', borderRadius: 999, padding: '2px 10px', whiteSpace: 'nowrap' }}>Dwibahasa (ID / EN)</span>
              </div>
              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 17, background: '#fff' }}>
                <thead>
                  <tr>
                    <th rowSpan={2} style={{ ...th, textAlign: 'left', background: '#eef2f7' }}>Uraian</th>
                    <th colSpan={5} style={{ ...th, textAlign: 'center', color: '#15803d', background: '#f0fdf4', borderRight: 'none' }}>Tahun</th>
                  </tr>
                  <tr style={{ background: '#f8fafc' }}>
                    {['2021', '2022', '2023', '2024', '2025'].map((y, i) => (
                      <th key={y} style={{ ...th, textAlign: 'center', borderRight: i === 4 ? 'none' : th.borderRight }}>{y}</th>
                    ))}
                  </tr>
                </thead>
              </table>
            </div>
          </Step>
        </Steps>
        <ModalFooter save="Simpan Kelompok Periode" />
      </ModalCard>
    </Tablet>
  </Stage>
);

// ============================================================================
// SLIDE 8 — PERAKITAN (generator + pratinjau langsung)
// ============================================================================
const Select = ({ label, badge, value }: { label: string; badge: string; value: string }) => (
  <div style={{ flex: 1 }}>
    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 17, fontWeight: 800, marginBottom: 6 }}>
      <span>{label}</span>
      <span style={{ fontWeight: 500, color: APP_SUB, border: `1px solid ${APP_LINE}`, borderRadius: 999, padding: '0 10px', fontSize: 15 }}>{badge}</span>
    </div>
    <div style={{ padding: '10px 12px', border: '1px solid #cbd5e1', borderRadius: 8, background: '#fff', fontSize: 17 }}>{value}</div>
  </div>
);

const Perakitan: Page = () => (
  <Stage>
    <div style={{ position: 'absolute', left: 130, top: 170, width: 720 }}>
      <Eyebrow style={rise(0)}>Perakitan</Eyebrow>
      <Headline size={86} style={{ marginTop: 20, ...rise(120) }}>Pilih tiga.<br />Lihat hasilnya.</Headline>
      <Lead style={{ marginTop: 36, ...rise(260) }}>
        Pilih kelompok baris, karakteristik kolom, dan periode di Generator. <Strong>Pratinjau matriks langsung terbentuk</Strong> sebelum disimpan.
      </Lead>
      <div style={{ marginTop: 52, display: 'flex', flexDirection: 'column', gap: 18, ...rise(420) }}>
        {[
          ['Baris', '#2563EB', 1],
          ['Kolom', '#EA580C', 1],
          ['Periode', '#16A34A', 1],
        ].map(([name, color], i) => (
          <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
            <div style={{ width: 300, height: 12, borderRadius: 6, background: 'rgb(226,226,230)' }}>
              <div style={{ width: '100%', height: '100%', borderRadius: 6, background: color as string, ...fillX(700 + i * 260) }} />
            </div>
            <span style={{ fontFamily: DISP, fontSize: 28, fontWeight: 600 }}>{name as string}</span>
          </div>
        ))}
      </div>
    </div>

    <Tablet left={900} top={120} width={930} height={800} delay={300}>
      <div style={{ display: 'flex', gap: 10, marginBottom: 16, alignItems: 'center' }}>
        <AppBtn bg={BLUE}>Pratinjau</AppBtn>
        <AppBtn bg={APP_GREEN}>Google Drive</AppBtn>
        <AppBtn bg="#475569">Edit Master</AppBtn>
        <span style={{ flex: 1 }} />
        <span style={{ fontSize: 16, fontWeight: 700, color: '#fff', background: 'linear-gradient(135deg,#4f46e5,#7c3aed)', borderRadius: 999, padding: '4px 14px' }}>Dinamis</span>
      </div>
      <div style={{ display: 'flex', gap: 12, marginBottom: 14 }}>
        <Select label="1. Dimensi Baris" badge="12 Master" value="Kabupaten/Kota (35 baris)" />
        <Select label="2. Karakteristik Kolom" badge="8 Master" value="Jenis Kelamin (3 item)" />
        <Select label="3. Periode" badge="4 Master" value="2023–2024 (2 periode)" />
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 14, fontSize: 17, color: APP_SUB }}>
        <b style={{ color: APP_TEXT }}>Tingkat:</b>
        {['Tingkat 1', 'Tingkat 2', 'Tingkat 3'].map((t) => (
          <span key={t} style={{ border: '1px solid #cbd5e1', borderRadius: 999, padding: '3px 12px', background: '#fff' }}>{t}</span>
        ))}
      </div>
      <div style={{ fontSize: 15, fontWeight: 800, color: APP_NAVY, letterSpacing: '0.04em', marginBottom: 8 }}>PRATINJAU MATRIKS</div>
      <div style={{ border: `1px solid ${APP_LINE}`, borderRadius: 8, overflow: 'hidden', ...loupe(1100) }}>
        <MatrixTable rows={ROWS.slice(0, 3)} fs={17} />
      </div>
    </Tablet>
  </Stage>
);

// ============================================================================
// SLIDE 7 — EKSPOR TEMPLATE EXCEL (jendela Excel + 4 penanda)
// ============================================================================
const XRow = ({ n, cells, head }: { n: number; cells: string[]; head?: boolean }) => (
  <div style={{ display: 'flex', borderBottom: '1px solid #d9dee5', background: head ? '#f1f5f9' : '#fff' }}>
    <div style={{ width: 54, background: '#eef0f3', color: '#8a94a3', textAlign: 'center', padding: '8px 0', fontSize: 17, borderRight: '1px solid #d9dee5' }}>{n}</div>
    {cells.map((c, i) => (
      <div key={i} style={{ flex: i === 0 ? 2.2 : 1, padding: '8px 12px', fontSize: 20, fontWeight: head ? 700 : 500, textAlign: i === 0 ? 'left' : head ? 'center' : 'right', borderRight: '1px solid #d9dee5', background: !head && n === 6 && i === 2 ? '#e8f5ee' : undefined, outline: !head && n === 6 && i === 2 ? '2.5px solid #107C41' : undefined, outlineOffset: -2 }}>{c}</div>
    ))}
  </div>
);

const Callout = ({ x, y, w = 400, title, sub, delay, up }: { x: number; y: number; w?: number; title: string; sub: string; delay: number; up?: boolean }) => (
  <div style={{ position: 'absolute', left: x, top: y, width: w, textAlign: 'center', ...rise(delay, 900) }}>
    <div style={{ fontFamily: DISP, fontSize: 32, fontWeight: 600, letterSpacing: '-0.015em', color: INK }}>{title}</div>
    <div style={{ fontFamily: BODY, fontSize: 21, fontWeight: 500, color: MUTED, marginTop: 6, lineHeight: 1.35 }}>{sub}</div>
    <div style={{ width: 2, height: 80, background: 'rgba(29,29,31,0.4)', margin: '14px auto 0', transformOrigin: up ? 'bottom' : 'top' }} />
  </div>
);

const EksporExcel: Page = () => (
  <Stage bg={ALT}>
    <div style={{ position: 'absolute', left: 0, top: 84, width: 1920, display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
      <Eyebrow style={rise(0)}>Ekspor</Eyebrow>
      <Headline size={96} style={{ marginTop: 14, ...rise(120) }}>Template Excel resmi.</Headline>
      <Lead style={{ marginTop: 14, color: INK, fontSize: 34, ...rise(240) }}>Satu klik pada <b>UNDUH TEMPLATE</b>. Strukturnya sudah jadi.</Lead>
    </div>

    <div style={{ position: 'absolute', left: 360, top: 470, width: 1200, borderRadius: 18, overflow: 'hidden', background: '#fff', boxShadow: 'rgba(0,0,0,0.16) 0px 30px 70px, rgba(0,0,0,0.08) 0px 0px 0px 1px', ...rise(420, 1000) }}>
      <div style={{ background: '#107C41', color: '#fff', padding: '12px 20px', fontSize: 20, fontWeight: 600, display: 'flex', alignItems: 'center', gap: 12 }}>
        <span style={{ width: 28, height: 28, borderRadius: 6, background: '#fff', color: '#107C41', fontWeight: 900, display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>X</span>
        Template_Tabel_Dinamis.xlsx
      </div>
      <XRow n={1} head cells={['', 'Jumlah Penduduk', '', '', '']} />
      <XRow n={2} head cells={['Kabupaten/Kota', '2023', '', '2024', '']} />
      <XRow n={3} head cells={['', 'L', 'P', 'L', 'P']} />
      <XRow n={4} head cells={['(1)', '(2)', '(3)', '(4)', '(5)']} />
      <XRow n={5} cells={['Cilacap', '1.002.314', '989.412', '', '']} />
      <XRow n={6} cells={['Banyumas', '912.440', '908.112', '', '']} />
      <div style={{ display: 'flex', background: '#eef0f3', padding: '8px 16px', gap: 10, fontSize: 18 }}>
        <span style={{ background: '#fff', padding: '3px 16px', borderRadius: '6px 6px 0 0', color: '#107C41', fontWeight: 700, borderBottom: '3px solid #107C41' }}>Tabel Data</span>
        <span style={{ padding: '3px 12px', color: '#8a94a3' }}>__METADATA_DDA__ (tersembunyi)</span>
      </div>
    </div>

    <Callout x={360} y={300 + 60} w={360} title="Header bertingkat" sub="Dibentuk otomatis dari pohon kolom." delay={1100} />
    <Callout x={760} y={300 + 60} w={360} title="Penomoran kolom" sub="(1) (2) (3) sesuai format tabel resmi." delay={1400} />
    <Callout x={1100} y={830} w={360} title="Sel isian" sub="Bagian yang diisi walidata." delay={1700} up />
    <Callout x={1400} y={830} w={360} title="Lembar metadata" sub="Dipakai untuk memetakan data saat impor." delay={2000} up />
  </Stage>
);

// ============================================================================
// SLIDE 8 — UNGGAH BERKAS (replika modal asli + Steps)
// ============================================================================
const Unggah: Page = () => (
  <Stage>
    <div style={{ position: 'absolute', left: 130, top: 230, width: 760 }}>
      <Eyebrow style={rise(0)}>Impor</Eyebrow>
      <Headline size={88} style={{ marginTop: 20, ...rise(120) }}>Unggah.<br />Terbaca otomatis.</Headline>
      <Lead style={{ marginTop: 36, ...rise(260) }}>
        Walidata mengisi template, admin mengunggahnya. <Strong>Sistem membaca lembar metadata, lalu menyimpan nilai ke tiap sel.</Strong>
      </Lead>
      <p style={{ fontFamily: BODY, fontSize: 28, fontWeight: 600, color: BLUE, margin: '34px 0 0 0', ...rise(420) }}>Klik untuk menjalankan simulasi ›</p>
    </div>

    <Tablet left={960} top={130} width={880} height={820} delay={300}>
      <div style={{ background: '#fff', borderRadius: 14, border: `1px solid ${APP_LINE}`, padding: 26, display: 'flex', flexDirection: 'column', flex: 1, boxShadow: '0 12px 30px rgba(15,23,42,0.08)' }}>
        <div style={{ fontSize: 26, fontWeight: 800, color: APP_TEXT, marginBottom: 14 }}>Upload Berkas Excel Tabel Dinamis</div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: 10, padding: '10px 14px', marginBottom: 16, fontSize: 17, color: '#166534' }}>
          <span>Gunakan template resmi tabel ini.</span>
          <AppBtn color={APP_GREEN} outline={APP_GREEN}>Unduh Template</AppBtn>
        </div>
        <div style={{ border: '2px dashed #94a3b8', background: '#f8fafc', borderRadius: 12, padding: '26px 20px', textAlign: 'center', marginBottom: 14 }}>
          <div style={{ fontSize: 22, fontWeight: 700 }}>Seret berkas ke sini</div>
          <div style={{ fontSize: 17, color: APP_SUB, margin: '6px 0 14px' }}>Format template resmi DDA berekstensi <b>.xlsx</b></div>
          <AppBtn bg={APP_GREEN}>Pilih Berkas</AppBtn>
        </div>
        <Steps>
          <Step duration={420}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', border: `1px solid ${APP_LINE}`, borderRadius: 10, padding: '10px 14px', fontSize: 18, marginBottom: 12 }}>
              <span><b>Template_Tabel_Dinamis.xlsx</b> <span style={{ color: APP_SUB }}>terpilih</span></span>
              <span style={{ color: '#dc2626', fontWeight: 700 }}>✕</span>
            </div>
          </Step>
          <Step duration={320}>
            <div style={{ marginBottom: 12 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 17, fontWeight: 700, color: '#047857', marginBottom: 8 }}>
                <span>Membaca &amp; memverifikasi metadata berkas...</span>
                <span style={{ background: '#d1fae5', padding: '1px 10px', borderRadius: 999 }}>Proses</span>
              </div>
              <div style={{ height: 10, borderRadius: 5, background: '#e2e8f0', overflow: 'hidden' }}>
                <div style={{ height: '100%', background: APP_GREEN, ...fillX(100, 1400) }} />
              </div>
            </div>
          </Step>
          <Step duration={320}>
            <div style={{ background: '#ecfdf5', border: '1px solid #a7f3d0', borderRadius: 10, padding: '12px 16px', fontSize: 18, color: '#065f46' }}>
              Data tabel dinamis berhasil diimpor! Total <b>140</b> sel data berhasil disimpan.
            </div>
          </Step>
        </Steps>
        <div style={{ marginTop: 'auto', fontSize: 16, color: APP_SUB }}>Jangan mengubah susunan kolom, baris, maupun struktur berkas template.</div>
      </div>
    </Tablet>
  </Stage>
);

// ============================================================================
// SLIDE 9 — SEKILAS (bento)
// ============================================================================
const Stat = ({ big, unit, label, x, y, delay }: { big: string; unit: string; label: string; x: number; y: number; delay: number }) => (
  <div style={{ position: 'absolute', left: x, top: y, width: 402, height: 360, borderRadius: 32, background: '#fff', boxShadow: 'rgba(0,0,0,0.06) 0px 10px 30px', padding: 40, boxSizing: 'border-box', display: 'flex', flexDirection: 'column', justifyContent: 'flex-end', ...rise(delay) }}>
    <div style={{ display: 'flex', alignItems: 'baseline', gap: 10, fontFamily: DISP, fontWeight: 600, letterSpacing: '-0.03em', lineHeight: 1 }}>
      <span style={{ fontSize: 118 }}>{big}</span>
      <span style={{ fontSize: 38, letterSpacing: '-0.01em', color: MUTED }}>{unit}</span>
    </div>
    <div style={{ fontSize: 26, fontWeight: 500, color: MUTED, marginTop: 14 }}>{label}</div>
  </div>
);

const Sekilas: Page = () => (
  <Stage bg={ALT}>
    <Headline size={76} style={{ position: 'absolute', left: 120, top: 96, ...rise(0) }}>Tabel Dinamis. Sekilas.</Headline>

    <div style={{ position: 'absolute', left: 120, top: 224, width: 828, height: 760, borderRadius: 32, background: '#fff', boxShadow: 'rgba(0,0,0,0.06) 0px 10px 30px', overflow: 'hidden', ...rise(160) }}>
      <div style={{ padding: '44px 48px 0' }}>
        <div style={{ fontFamily: DISP, fontSize: 46, fontWeight: 600, letterSpacing: '-0.02em' }}>Tabel Dinamis</div>
        <div style={{ fontSize: 26, color: MUTED, marginTop: 8 }}>Rakit, ekspor, isi, impor.</div>
      </div>
      <div style={{ position: 'absolute', left: 48, top: 200, width: 732, borderRadius: 18, overflow: 'hidden', border: `1px solid ${APP_LINE}`, ...loupe(700) }}>
        <MatrixTable rows={ROWS.slice(0, 3)} fs={19} />
      </div>
    </div>

    <Stat big="3" unit="dimensi" label="Baris, kolom, periode" x={972} y={224} delay={320} />
    <Stat big="3" unit="level" label="Hierarki kolom" x={1398} y={224} delay={440} />
    <Stat big="1" unit="berkas" label="Template Excel resmi" x={972} y={624} delay={560} />
    <Stat big="4" unit="peran" label="Data, Kategori, Subtotal, Total" x={1398} y={624} delay={680} />
  </Stage>
);

// ============================================================================
// SLIDE 10 — SIAP RILIS (tampilan publik + tombol)
// ============================================================================
const Rilis: Page = () => (
  <Stage>
    <Tablet left={140} top={190} width={900} height={820} delay={200}>
      <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 16 }}>
        <span style={{ background: APP_NAVY, color: '#fff', borderRadius: 999, padding: '9px 22px', fontSize: 18, fontWeight: 700 }}>Unduh Data ▾</span>
      </div>
      <div style={{ fontSize: 28, fontWeight: 800, color: APP_NAVY, marginBottom: 16 }}>Jumlah Penduduk Menurut Kabupaten/Kota dan Jenis Kelamin</div>
      <div style={{ border: `1px solid ${APP_LINE}`, borderRadius: 8, overflow: 'hidden' }}>
        <MatrixTable rows={ROWS} fs={19} />
      </div>
      <div style={{ marginTop: 14, fontSize: 18, color: APP_SUB }}><b>Sumber/</b><i>Source</i>: BPS Provinsi Jawa Tengah</div>
    </Tablet>

    <div style={{ position: 'absolute', left: 1120, top: 270, width: 700 }}>
      <Eyebrow style={{ fontSize: 34, ...rise(0) }}>Publikasi</Eyebrow>
      <h2 style={{ ...gradientText, fontSize: 124, marginTop: 14, ...rise(120) }}>Siap tayang.</h2>
      <Lead style={{ marginTop: 28, fontSize: 32, ...rise(260) }}>
        Setelah data diimpor, <Strong>tabel langsung tampil di halaman publik</Strong> lengkap dengan menu Unduh Data (Excel dan PDF).
      </Lead>
      <div style={{ display: 'flex', alignItems: 'center', gap: 34, marginTop: 48, ...rise(420) }}>
        <span style={{ background: BLUE, color: '#fff', fontSize: 28, fontWeight: 600, letterSpacing: '-0.01em', padding: '16px 40px', borderRadius: 999 }}>{''}</span>
        <span style={{ color: BLUE, fontSize: 28, fontWeight: 600, letterSpacing: '-0.01em' }}>{''}</span>
      </div>
    </div>
  </Stage>
);

// ============================================================================
export default [Cover, Statement, Arsitektur, MasterDimensi, Hierarki, Baris, Periode, Perakitan, EksporExcel, Unggah, Sekilas, Rilis] satisfies Page[];

export const notes = [
  'Modul Tabel Dinamis pada DDA Online BPS Provinsi Jawa Tengah. Tampilan di tablet adalah halaman pratinjau tabel di aplikasi.',
  'Inti idenya: struktur tabel disusun sekali, angkanya diisi lewat satu berkas Excel. Klik untuk memunculkan tiap kalimat.',
  'Setiap angka punya tiga koordinat: baris, kolom, dan periode. Garis penanda muncul satu per satu.',
  'Master Dimensi menyimpan kelompok karakteristik, baris, dan periode agar bisa dipakai ulang oleh banyak tabel.',
  'Kolom bisa bertingkat hingga tiga level. Setiap node punya peran: Data, Kategori, Subtotal, atau Total.',
  'Membuat kelompok baris: isi nama kelompok dan judul header, tambahkan item baris, lalu pakai Generate untuk penomoran otomatis per level. Klik tiga kali untuk melihat urutannya.',
  'Membuat kelompok periode: isi nama kelompok dan judul header, tambahkan periode, lalu lihat Live Preview posisi kolom waktu pada tabel. Klik dua kali untuk melihat urutannya.',
  'Di Generator, pilih baris, kolom, dan periode. Pratinjau matriks langsung muncul sebelum disimpan.',
  'Tombol UNDUH TEMPLATE menghasilkan berkas Excel dengan header bertingkat, penomoran kolom, dan lembar metadata tersembunyi.',
  'Unggah berkas yang sudah diisi. Klik tiga kali untuk melihat urutan: berkas terpilih, verifikasi metadata, lalu hasil impor.',
  'Ringkasan: tiga dimensi, tiga level hierarki, satu berkas template, empat peran sel.',
  'Setelah impor, tabel tampil di halaman publik dengan menu Unduh Data.',
];

export const meta: SlideMeta = {
  title: 'Panduan Modul Tabel Dinamis — DDA Online BPS Jateng',
  createdAt: '2026-10-28T19:00:00.000Z',
};
