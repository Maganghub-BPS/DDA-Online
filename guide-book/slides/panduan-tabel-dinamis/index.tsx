import type { CSSProperties, ReactNode } from 'react';
import type { DesignSystem, Page, SlideMeta, SlideTransition } from '@open-slide/core';

// ============================================================================
// BUKU PANDUAN PENGGUNAAN SISTEM TABEL DINAMIS — DDA ONLINE BPS JATENG
// Tema: Blue and Yellow Modern Business Project Guide Booklet (Canva DAHZQx3FP-Q)
// Format Kanvas: A4 Portrait Proportions (1024 x 1448 px Ratio)
// Tampilan Tabel: 100% IDENTIK dengan v_tabel_dinamis.php asli BPS Jateng
// Alur SOP: 11 Halaman Lengkap & Urut Kronologis (Tanpa Hal 10 Matching PDF)
// ============================================================================

export const design: DesignSystem = {
  palette: { bg: '#080E1A', text: '#0F172A', accent: '#0B2E59' },
  fonts: {
    display: '"Plus Jakarta Sans", "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    body: '"Plus Jakarta Sans", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
  },
  typeScale: { hero: 160, body: 32 },
  radius: 8,
};

export const transition: SlideTransition = {
  duration: 280,
  exit: {
    duration: 200,
    easing: 'cubic-bezier(0.4, 0, 1, 1)',
    keyframes: [{ opacity: 1 }, { opacity: 0.9 }],
  },
  enter: {
    duration: 280,
    easing: 'cubic-bezier(0, 0, 0.2, 1)',
    keyframes: [{ opacity: 0, transform: 'scale(0.99)' }, { opacity: 1, transform: 'scale(1)' }],
  },
};

// ----------------------------------------------------------------------------
// Token Warna Canva & BPS
// ----------------------------------------------------------------------------
const NAVY_DARK = '#071E3D';
const NAVY_PRIMARY = '#0B2E59';
const NAVY_LIGHT = '#1F4287';
const YELLOW_GOLD = '#FFC107';
const YELLOW_BRIGHT = '#FFD54F';
const YELLOW_LIGHT = '#FFF9E6';

const BPS_ORANGE = '#FF6D1F';
const BPS_PEACH = '#f9a55f';
const BPS_ODD = '#fde5ce';
const BPS_EVEN = '#fef3e6';
const BPS_BANNER = '#fff1e6';

const TEXT_DARK = '#0F172A';
const TEXT_MUTED = '#64748B';
const BORDER_LIGHT = '#E2E8F0';

// ----------------------------------------------------------------------------
// Frame Booklet Shell Berpresisi A4 Canva (Rasio 1024 x 1448 -> 712 x 1006 px)
// Diletakkan presisi di tengah layar 1920 x 1080 dengan studio dark reader
// ----------------------------------------------------------------------------
const A4BookletFrame = ({
  chapterTag,
  chapterTitle,
  pageNumber,
  children,
  isCover = false,
}: {
  chapterTag?: string;
  chapterTitle?: string;
  pageNumber?: number;
  children: ReactNode;
  isCover?: boolean;
}) => (
  <div
    style={{
      width: 1920,
      height: 1080,
      background: '#070D18 radial-gradient(#1E293B 1px, transparent 1px)',
      backgroundSize: '24px 24px',
      position: 'relative',
      overflow: 'hidden',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontFamily: 'var(--osd-font-body)',
      WebkitFontSmoothing: 'antialiased',
    }}
  >
    {/* Left Executive Desk Indicator */}
    <div
      style={{
        position: 'absolute',
        left: 64,
        top: 40,
        bottom: 40,
        width: 440,
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'space-between',
        color: '#94A3B8',
        zIndex: 5,
      }}
    >
      <div>
        <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: 'rgba(255,255,255,0.06)', padding: '5px 12px', borderRadius: 4, borderLeft: `3px solid ${YELLOW_GOLD}`, marginBottom: 14 }}>
          <span style={{ fontSize: 11, fontWeight: 800, color: YELLOW_GOLD, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
            SOP BPS JAWA TENGAH
          </span>
        </div>
        <h3 style={{ fontSize: 20, fontWeight: 800, color: '#FFFFFF', lineHeight: 1.3, letterSpacing: '-0.01em', marginBottom: 8 }}>
          Buku Panduan Sistem Tabel Dinamis
        </h3>
        <p style={{ fontSize: 13, color: '#64748B', lineHeight: 1.5 }}>
          Format Dokumen: <b>A4 Portrait (1024 × 1448 px)</b><br />
          Tema: <i>Blue & Yellow Modern Business Booklet</i>
        </p>
      </div>

      <div style={{ background: 'rgba(15, 23, 42, 0.7)', border: '1px solid rgba(255, 255, 255, 0.08)', borderRadius: 8, padding: 16 }}>
        <div style={{ fontSize: 11, fontWeight: 700, color: YELLOW_BRIGHT, textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: 4 }}>
          Navigasi Halaman Buku
        </div>
        <div style={{ fontSize: 13, color: '#E2E8F0', fontWeight: 600 }}>
          Halaman <span style={{ color: YELLOW_GOLD, fontSize: 16, fontWeight: 800 }}>{pageNumber || 1}</span> dari 11
        </div>
        <div style={{ fontSize: 11, color: '#64748B', marginTop: 4 }}>
          Tekan tombol panah keyboard [←] [→] untuk membalik halaman.
        </div>
      </div>
    </div>

    {/* Right Desk Action Widget */}
    <div
      style={{
        position: 'absolute',
        right: 64,
        top: 40,
        bottom: 40,
        width: 440,
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'space-between',
        alignItems: 'flex-end',
        color: '#94A3B8',
        zIndex: 5,
        textAlign: 'right',
      }}
    >
      <div>
        <span style={{ background: BPS_ORANGE, color: '#FFFFFF', padding: '5px 14px', borderRadius: 50, fontSize: 12, fontWeight: 700, display: 'inline-block' }}>
          DDA Online v2.4
        </span>
        <div style={{ fontSize: 12, color: '#94A3B8', marginTop: 8 }}>
          BPS Provinsi Jawa Tengah<br />
          Publikasi Statistik Resmi
        </div>
      </div>

      <div style={{ background: 'rgba(15, 23, 42, 0.7)', border: '1px solid rgba(255, 255, 255, 0.08)', borderRadius: 8, padding: 16, textAlign: 'right' }}>
        <div style={{ fontSize: 12, fontWeight: 700, color: '#FFFFFF', marginBottom: 4 }}>
          Versi Cetak PDF Tersedia
        </div>
        <div style={{ fontSize: 11, color: '#64748B', lineHeight: 1.4 }}>
          File standalone HTML ukuran penuh 1024x1448 px siap cetak / simpan ke PDF di folder documentation.
        </div>
      </div>
    </div>

    {/* The Authentic A4 Booklet Canvas (712 x 1006 px) */}
    <div
      style={{
        width: 712,
        height: 1006,
        background: isCover ? NAVY_DARK : '#FFFFFF',
        color: isCover ? '#FFFFFF' : TEXT_DARK,
        borderRadius: 4,
        boxShadow: '0 25px 60px rgba(0, 0, 0, 0.65), 0 2px 8px rgba(0, 0, 0, 0.4)',
        position: 'relative',
        overflow: 'hidden',
        display: 'flex',
        flexDirection: 'column',
        isolation: 'isolate',
        zIndex: 10,
      }}
    >
      {/* Header Strip Canva (Non-Cover) */}
      {!isCover && (
        <div
          style={{
            height: 64,
            background: NAVY_PRIMARY,
            color: '#FFFFFF',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '0 32px',
            position: 'relative',
            overflow: 'hidden',
            flexShrink: 0,
          }}
        >
          {/* Top Yellow Ribbon */}
          <div style={{ position: 'absolute', top: 0, left: 0, width: 200, height: 6, background: YELLOW_GOLD }} />
          
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, zIndex: 2 }}>
            <span
              style={{
                background: YELLOW_GOLD,
                color: NAVY_DARK,
                fontWeight: 800,
                fontSize: 10.5,
                letterSpacing: '0.08em',
                padding: '3px 8px',
                borderRadius: 3,
                textTransform: 'uppercase',
              }}
            >
              {chapterTag || 'SOP DDA'}
            </span>
            <span style={{ fontSize: 12, fontWeight: 700, color: '#FFFFFF', letterSpacing: '0.02em' }}>
              {chapterTitle || 'Buku Panduan Sistem Tabel Dinamis'}
            </span>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: 10, zIndex: 2 }}>
            <span
              style={{
                width: 26,
                height: 26,
                borderRadius: '50%',
                background: YELLOW_GOLD,
                color: NAVY_DARK,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontWeight: 800,
                fontSize: 11,
              }}
            >
              {pageNumber ? String(pageNumber).padStart(2, '0') : '01'}
            </span>
          </div>

          {/* Diagonal Gradient Accent Canva */}
          <div
            style={{
              position: 'absolute',
              right: 0,
              top: 0,
              bottom: 0,
              width: 120,
              background: 'linear-gradient(135deg, transparent 40%, rgba(255, 193, 7, 0.25) 40%, rgba(255, 193, 7, 0.85) 100%)',
              pointerEvents: 'none',
            }}
          />
        </div>
      )}

      {/* Body Content */}
      <div
        style={{
          padding: isCover ? '46px 40px' : '24px 32px',
          flex: 1,
          display: 'flex',
          flexDirection: 'column',
          overflow: 'hidden',
          boxSizing: 'border-box',
        }}
      >
        {children}
      </div>

      {/* Footer Strip Canva (Non-Cover) */}
      {!isCover && (
        <div
          style={{
            height: 38,
            background: '#F8FAFC',
            borderTop: `1px solid ${BORDER_LIGHT}`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '0 32px',
            fontSize: 10,
            color: TEXT_MUTED,
            position: 'relative',
            flexShrink: 0,
          }}
        >
          <span>SOP Tabel Dinamis DDA • BPS Provinsi Jawa Tengah</span>
          <span>Halaman {pageNumber || 1} dari 11</span>
          <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 3, background: NAVY_PRIMARY }} />
          <div style={{ position: 'absolute', bottom: 0, right: 0, width: 80, height: 3, background: YELLOW_GOLD }} />
        </div>
      )}
    </div>
  </div>
);

// ----------------------------------------------------------------------------
// REPLIKA TABEL IDENTIK v_tabel_dinamis.php ASLI BPS
// ----------------------------------------------------------------------------
const IdenticBpsTableMockup = ({ scale = 1 }: { scale?: number }) => (
  <div
    style={{
      background: '#FFFFFF',
      border: '1px solid #CBD5E1',
      borderRadius: 8,
      padding: 12,
      boxShadow: '0 2px 10px rgba(0, 0, 0, 0.04)',
      width: '100%',
      boxSizing: 'border-box',
      transform: scale !== 1 ? `scale(${scale})` : undefined,
      transformOrigin: 'top left',
    }}
  >
    {/* Toolbar Asli */}
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8, borderBottom: '1px solid #F1F5F9', paddingBottom: 6 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
        <span style={{ background: '#F8FAFC', color: '#64748B', border: '1px solid #E2E8F0', borderRadius: 50, padding: '3px 10px', fontSize: 9.5, fontWeight: 600 }}>
          ‹ Kembali
        </span>
        <span style={{ fontSize: 10.5, fontWeight: 600, color: '#334155' }}>
          Tahun: <b style={{ color: BPS_ORANGE }}>2024</b>
        </span>
      </div>
      <div style={{ display: 'flex', gap: 6 }}>
        <span style={{ background: 'linear-gradient(135deg, #059669, #10B981)', color: '#FFFFFF', borderRadius: 50, padding: '3px 10px', fontSize: 9.5, fontWeight: 700 }}>
          UPLOAD EXCEL
        </span>
        <span style={{ background: BPS_ORANGE, color: '#FFFFFF', borderRadius: 50, padding: '3px 10px', fontSize: 9.5, fontWeight: 700 }}>
          EXPORT EXCEL
        </span>
      </div>
    </div>

    {/* Header Title Kotak BPS */}
    <div style={{ display: 'flex', alignItems: 'stretch', gap: 8, marginBottom: 8 }}>
      <div style={{ border: '1px solid #000000', padding: '4px 8px', fontSize: 10, fontWeight: 'bold', color: '#000000', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#FAFAFA', lineHeight: 1.2, flexShrink: 0 }}>
        Tabel<br />1.1.1
      </div>
      <div>
        <div style={{ fontWeight: 'bold', fontSize: 10.5, color: '#000000', lineHeight: 1.25 }}>
          Jumlah Penduduk Menurut Kabupaten/Kota dan Jenis Kelamin di Provinsi Jawa Tengah, 2024
        </div>
        <div style={{ fontStyle: 'italic', fontSize: 9.5, color: '#333333', lineHeight: 1.25 }}>
          Population by Regency/Municipality and Sex in Jawa Tengah Province, 2024
        </div>
      </div>
    </div>

    {/* Thead Oranye & Body Belang Asli */}
    <table style={{ width: '100%', borderCollapse: 'collapse', borderTop: '1px solid #000000', fontFamily: 'Arial, sans-serif', fontSize: 9.5 }}>
      <thead>
        <tr>
          <th rowSpan={2} style={{ background: BPS_ORANGE, color: '#FFFFFF', border: '1px solid #FFFFFF', padding: '4px', fontWeight: 'bold', textAlign: 'center', width: '34%' }}>
            Kabupaten/Kota<br /><i style={{ fontWeight: 'normal', fontSize: '0.85em', color: 'rgba(255,255,255,0.95)' }}>Regency/Municipality</i>
          </th>
          <th colSpan={2} style={{ background: BPS_ORANGE, color: '#FFFFFF', border: '1px solid #FFFFFF', padding: '4px', fontWeight: 'bold', textAlign: 'center' }}>
            Jenis Kelamin / <i>Sex</i>
          </th>
          <th rowSpan={2} style={{ background: BPS_ORANGE, color: '#FFFFFF', border: '1px solid #FFFFFF', padding: '4px', fontWeight: 'bold', textAlign: 'center', width: '22%' }}>
            Jumlah<br /><i style={{ fontWeight: 'normal', fontSize: '0.85em', color: 'rgba(255,255,255,0.95)' }}>Total</i>
          </th>
        </tr>
        <tr>
          <th style={{ background: BPS_ORANGE, color: '#FFFFFF', border: '1px solid #FFFFFF', padding: '3px', fontWeight: 'bold', textAlign: 'center' }}>Laki-Laki</th>
          <th style={{ background: BPS_ORANGE, color: '#FFFFFF', border: '1px solid #FFFFFF', padding: '3px', fontWeight: 'bold', textAlign: 'center' }}>Perempuan</th>
        </tr>
        <tr style={{ background: BPS_PEACH, color: '#000000', fontWeight: 'bold', fontSize: 9 }}>
          <th style={{ border: '1px solid #FFFFFF', padding: '2px', textAlign: 'center' }}>(1)</th>
          <th style={{ border: '1px solid #FFFFFF', padding: '2px', textAlign: 'center' }}>(2)</th>
          <th style={{ border: '1px solid #FFFFFF', padding: '2px', textAlign: 'center' }}>(3)</th>
          <th style={{ border: '1px solid #FFFFFF', padding: '2px', textAlign: 'center' }}>(4)</th>
        </tr>
      </thead>
      <tbody>
        <tr style={{ background: BPS_BANNER, fontWeight: 'bold', fontStyle: 'italic', color: '#2B1704' }}>
          <td colSpan={4} style={{ border: '1px solid #FFFFFF', padding: '3px 6px' }}>Kabupaten / Regency</td>
        </tr>
        <tr style={{ background: BPS_ODD }}>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px' }}>01. Cilacap</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right' }}>1 007 982</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right' }}>998 420</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right', fontWeight: 'bold' }}>2 006 402</td>
        </tr>
        <tr style={{ background: BPS_EVEN }}>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px' }}>02. Banyumas</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right' }}>912 300</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right' }}>905 110</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right', fontWeight: 'bold' }}>1 817 410</td>
        </tr>
        <tr style={{ background: BPS_BANNER, fontWeight: 'bold', fontStyle: 'italic', color: '#2B1704' }}>
          <td colSpan={4} style={{ border: '1px solid #FFFFFF', padding: '3px 6px' }}>Kota / Municipality</td>
        </tr>
        <tr style={{ background: BPS_ODD }}>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px' }}>73. Kota Semarang</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right' }}>840 500</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right' }}>865 300</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '3px 6px', textAlign: 'right', fontWeight: 'bold' }}>1 705 800</td>
        </tr>
        <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', fontWeight: 'bold' }}>
          <td style={{ border: '1px solid #FFFFFF', padding: '4px 6px' }}>JAWA TENGAH</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '4px 6px', textAlign: 'right' }}>18 720 100</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '4px 6px', textAlign: 'right' }}>18 910 200</td>
          <td style={{ border: '1px solid #FFFFFF', padding: '4px 6px', textAlign: 'right' }}>37 630 300</td>
        </tr>
      </tbody>
    </table>

    <div style={{ marginTop: 6, fontSize: 8.5, color: '#475569', borderTop: '1px solid #E2E8F0', paddingTop: 4 }}>
      <b>Sumber / Source:</b> BPS Provinsi Jawa Tengah • <b>Catatan / Note:</b> Angka proyeksi SP2020.
    </div>
  </div>
);

// ============================================================================
// 11 HALAMAN BUKU PANDUAN KRONOLOGIS & URUT (A4 CANVA EDITION)
// ============================================================================

// ----------------------------------------------------------------------------
// HALAMAN 01: COVER
// ----------------------------------------------------------------------------
const Slide01Cover: Page = () => (
  <A4BookletFrame isCover pageNumber={1}>
    <div style={{ position: 'absolute', top: 0, right: 0, width: 320, height: 320, background: `linear-gradient(135deg, ${YELLOW_BRIGHT}, ${YELLOW_GOLD})`, clipPath: 'polygon(100% 0, 0 0, 100% 100%)' }} />
    <div style={{ position: 'absolute', bottom: 0, left: 0, width: 240, height: 12, background: YELLOW_GOLD }} />

    <div style={{ height: '100%', display: 'flex', flexDirection: 'column', justifyContent: 'space-between', zIndex: 2 }}>
      <div>
        <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: 'rgba(255,255,255,0.12)', padding: '5px 12px', borderRadius: 4, borderLeft: `3px solid ${YELLOW_GOLD}`, marginBottom: 24 }}>
          <span style={{ fontSize: 10, fontWeight: 800, letterSpacing: '0.08em', color: YELLOW_GOLD, textTransform: 'uppercase' }}>
            BPS JATENG • SOP OPERASIONAL RESMI
          </span>
        </div>

        <h1 style={{ fontSize: 34, fontWeight: 800, lineHeight: 1.15, letterSpacing: '-0.02em', color: '#FFFFFF', marginBottom: 14 }}>
          BUKU PANDUAN SISTEM<br />
          <span style={{ color: YELLOW_GOLD }}>TABEL DINAMIS</span> DDA
        </h1>

        <p style={{ fontSize: 13.5, color: '#CBD5E1', lineHeight: 1.5, marginBottom: 26, maxWidth: 560 }}>
          Pedoman Komprehensif Arsitektur Data 3D, Setup Master Dimensi, Generator Matriks, Distribusi OPD, dan Publikasi Web Resmi BPS Jawa Tengah.
        </p>

        {/* 3D Formula Card */}
        <div style={{ background: 'rgba(255, 255, 255, 0.06)', border: '1px solid rgba(255, 255, 255, 0.12)', borderRadius: 8, padding: 18, maxWidth: 620 }}>
          <div style={{ fontSize: 10.5, fontWeight: 700, color: YELLOW_BRIGHT, textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: 8 }}>
            ⚡ Formulasi Matriks Data 3-Dimensi
          </div>
          <div style={{ fontFamily: 'monospace', fontSize: 15, fontWeight: 700, color: '#FFFFFF', background: 'rgba(0,0,0,0.3)', padding: '10px 14px', borderRadius: 6, borderLeft: `4px solid ${BPS_ORANGE}`, marginBottom: 8 }}>
            f(Dimensi Baris, Dimensi Kolom, Periode) → Nilai Sel
          </div>
          <div style={{ fontSize: 11, color: '#94A3B8', lineHeight: 1.45 }}>
            Pemisahan total antara struktur matriks dengan nilai angka untuk menjamin konsistensi format BPS dan mendukung pengisian multi-metode (PDF, Excel, dan Web).
          </div>
        </div>
      </div>

      <div style={{ borderTop: '1px solid rgba(255, 255, 255, 0.2)', paddingTop: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}>
        <div>
          <div style={{ fontSize: 12.5, fontWeight: 700, color: '#FFFFFF' }}>Badan Pusat Statistik Provinsi Jawa Tengah</div>
          <div style={{ fontSize: 10.5, color: '#94A3B8' }}>Tim Pengolahan Data & DDA Online • Edisi 2025/2026</div>
        </div>
        <div style={{ background: YELLOW_GOLD, color: NAVY_DARK, fontWeight: 800, fontSize: 11, padding: '4px 12px', borderRadius: 3 }}>
          A4 BOOKLET
        </div>
      </div>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 02: DAFTAR ISI & FILOSOFI DATA 3D
// ----------------------------------------------------------------------------
const Slide02DaftarIsi: Page = () => (
  <A4BookletFrame chapterTag="PENDAHULUAN" chapterTitle="Daftar Isi & Filosofi Data 3D" pageNumber={2}>
    <div style={{ marginBottom: 16 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        STRUKTUR OPERASIONAL
      </div>
      <h2 style={{ fontSize: 20, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Peta Panduan & Filosofi Arsitektur Data 3D
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, marginBottom: 14 }}>
      {/* Table of contents */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 14 }}>
        <div style={{ fontSize: 12, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 10, display: 'flex', alignItems: 'center', gap: 6 }}>
          <span>📑</span> Daftar 9 Bab Panduan
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10.5 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 1:</b> Setup Dimensi Baris & Auto No</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 03</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 2:</b> Setup Dimensi Kolom (`||`)</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 04</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 3:</b> Setup Dimensi Periode</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 05</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 4:</b> Perakitan Generator Matriks</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 06</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 5:</b> Metode A: Injeksi Cepat PDF</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 07</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 6:</b> Metode B: Distribusi Excel OPD</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 08</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 7:</b> Metode C: Inline Edit Web</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 09</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #E2E8F0', paddingBottom: 2 }}>
            <span><b>Bab 8:</b> Publikasi Resmi Portal Publik</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 10</b>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: 2 }}>
            <span><b>Bab 9:</b> Ringkasan & Standar Notasi</span>
            <b style={{ color: NAVY_PRIMARY }}>Hal 11</b>
          </div>
        </div>
      </div>

      {/* 3D Concept Card */}
      <div style={{ background: NAVY_DARK, color: '#FFFFFF', borderRadius: 8, padding: 14 }}>
        <div style={{ fontSize: 12, fontWeight: 700, color: YELLOW_GOLD, marginBottom: 6, display: 'flex', alignItems: 'center', gap: 6 }}>
          <span>🧊</span> Keunggulan Arsitektur 3D
        </div>
        <p style={{ fontSize: 10.5, color: '#CBD5E1', lineHeight: 1.45, marginBottom: 8 }}>
          Sistem tabel statis lama rentan rusak ketika ada penambahan tahun atau kolom baru.
        </p>
        <div style={{ background: 'rgba(255,255,255,0.08)', padding: 8, borderRadius: 6, fontSize: 10, lineHeight: 1.5 }}>
          • <b>Reusable:</b> Kelompok 35 Kab/Kota bisa dipakai pada 50+ tabel berbeda.<br />
          • <b>Multi-Tahun:</b> Tambah tahun 2025 tanpa mengganggu struktur 2024.<br />
          • <b>Standar BPS:</b> Otomatisasi nomor kolom, selang-seling warna, & total.
        </div>
      </div>
    </div>

    {/* 4 Main Stages */}
    <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 8 }}>
        4 Tahap Besar Pengelolaan Tabel Dinamis:
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 8, textAlign: 'center' }}>
        <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 6, padding: 8 }}>
          <div style={{ fontSize: 14 }}>1️⃣</div>
          <div style={{ fontWeight: 700, fontSize: 10.5, color: NAVY_PRIMARY }}>Setup Dimensi</div>
          <div style={{ fontSize: 9.5, color: TEXT_MUTED }}>Baris, Kolom, Waktu</div>
        </div>
        <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 6, padding: 8 }}>
          <div style={{ fontSize: 14 }}>2️⃣</div>
          <div style={{ fontWeight: 700, fontSize: 10.5, color: NAVY_PRIMARY }}>Rakit Matriks</div>
          <div style={{ fontSize: 9.5, color: TEXT_MUTED }}>Generator & Desimal</div>
        </div>
        <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 6, padding: 8 }}>
          <div style={{ fontSize: 14 }}>3️⃣</div>
          <div style={{ fontWeight: 700, fontSize: 10.5, color: NAVY_PRIMARY }}>Isi Nilai</div>
          <div style={{ fontSize: 9.5, color: TEXT_MUTED }}>PDF, Excel, Web</div>
        </div>
        <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 6, padding: 8 }}>
          <div style={{ fontSize: 14 }}>4️⃣</div>
          <div style={{ fontWeight: 700, fontSize: 10.5, color: NAVY_PRIMARY }}>Publikasi Web</div>
          <div style={{ fontSize: 9.5, color: TEXT_MUTED }}>Filter & Ekspor</div>
        </div>
      </div>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 03: BAB 1: SETUP DIMENSI BARIS
// ----------------------------------------------------------------------------
const Slide03DimensiBaris: Page = () => (
  <A4BookletFrame chapterTag="BAB 01" chapterTitle="Setup Dimensi Baris" pageNumber={3}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        MENU: admin/master_dimensi
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Konfigurasi Dimensi Baris & Auto-Penomoran BPS
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* Steps */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 8 }}>
          Langkah Pembuatan Baris
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8, fontSize: 10.5 }}>
          <div>
            <b>1. Buka Tab Judul Baris:</b> Masuk menu <i>Master Dimensi</i>.
          </div>
          <div>
            <b>2. Tambah Kelompok:</b> Misal: <span style={{ fontFamily: 'monospace', background: '#F1F5F9', padding: '1px 4px', borderRadius: 3 }}>35 Kab/Kota Jateng</span>.
          </div>
          <div>
            <b>3. Input Item Baris:</b> Masukkan nama Indonesia & English, serta tentukan level indentasinya (0, 1, 2).
          </div>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 10, fontSize: 10, color: '#5C3E00', lineHeight: 1.4 }}>
          <b>⚡ Fitur Auto-Generate Nomor BPS:</b> Klik 1 tombol untuk penomoran otomatis A, B, C (kelompok), 1, 2, 3 (item utama), dan a, b, c (rincian).
        </div>
      </div>

      {/* 4 Row Types */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          4 Tipe Baris Standar BPS
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 9.8 }}>
          <div style={{ background: '#F8FAFC', borderLeft: '3px solid #3B82F6', padding: '5px 8px', borderRadius: 3 }}>
            <b>`data` (Nilai Normal):</b> Diisi angka. Tampil selang-seling peach (#fde5ce / #fef3e6).
          </div>
          <div style={{ background: BPS_BANNER, borderLeft: `3px solid ${BPS_ORANGE}`, padding: '5px 8px', borderRadius: 3 }}>
            <b>`kategori` (Banner):</b> Judul kelompok tebal tanpa angka (contoh: <i>Kabupaten / Kota</i>).
          </div>
          <div style={{ background: '#FFF7ED', borderLeft: `3px solid ${BPS_PEACH}`, padding: '5px 8px', borderRadius: 3 }}>
            <b>`subtotal`:</b> Sub-jumlah kelompok dengan background peach tebal (#f9a55f).
          </div>
          <div style={{ background: '#FFF1E6', borderLeft: `3px solid ${BPS_ORANGE}`, padding: '5px 8px', borderRadius: 3 }}>
            <b>`total`:</b> Grand Total Oranye BPS (#FF6D1F) dengan teks putih tebal di baris akhir.
          </div>
        </div>
      </div>
    </div>

    {/* Table Visual Demonstration */}
    <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 10 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
        Pratinjau Hasil Konfigurasi Tipe Baris pada Matriks Asli:
      </div>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 9.5, fontFamily: 'Arial, sans-serif' }}>
        <thead>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'left' }}>
            <th style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>Kabupaten/Kota</th>
            <th style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>Tipe Baris</th>
            <th style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>Warna Latar</th>
            <th style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>Status Input</th>
          </tr>
        </thead>
        <tbody>
          <tr style={{ background: BPS_BANNER, fontWeight: 'bold', fontStyle: 'italic' }}>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>Kabupaten / Regency</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>`kategori`</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>#fff1e6 (Banner)</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>Terkunci (Tanpa Nilai)</td>
          </tr>
          <tr style={{ background: BPS_ODD }}>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>01. Cilacap</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>`data`</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>#fde5ce (Soft Peach)</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>Wajib Diisi Angka</td>
          </tr>
          <tr style={{ background: BPS_EVEN }}>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>02. Banyumas</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>`data`</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>#fef3e6 (Soft Cream)</td>
            <td style={{ padding: '3px 6px', border: '1px solid #FFFFFF' }}>Wajib Diisi Angka</td>
          </tr>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', fontWeight: 'bold' }}>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>JAWA TENGAH</td>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>`total`</td>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>#FF6D1F (Oranye BPS)</td>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>Grand Total Kumulatif</td>
          </tr>
        </tbody>
      </table>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 04: BAB 2: SETUP DIMENSI KOLOM
// ----------------------------------------------------------------------------
const Slide04DimensiKolom: Page = () => (
  <A4BookletFrame chapterTag="BAB 02" chapterTitle="Setup Dimensi Kolom" pageNumber={4}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        MENU: admin/master_dimensi
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Hierarki Kolom Bertingkat (`||`) & Satuan Data
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* Kolom bertingkat syntax */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Sintaks Kolom Bertingkat (`||`)
        </div>
        <p style={{ fontSize: 10.5, color: TEXT_MUTED, lineHeight: 1.4, marginBottom: 8 }}>
          Gunakan pemisah ganda <span style={{ fontFamily: 'monospace', background: '#F1F5F9', padding: '1px 4px', borderRadius: 3 }}>||</span> untuk membuat sub-kolom tanpa perlu menulis kode HTML:
        </p>

        <div style={{ background: '#0F172A', color: '#F8FAFC', padding: 10, borderRadius: 6, fontFamily: 'monospace', fontSize: 10, lineHeight: 1.5, marginBottom: 8 }}>
          <span style={{ color: '#F59E0B' }}>Distribusi || PBF</span><br />
          <span style={{ color: '#F59E0B' }}>Distribusi || Gudang</span><br />
          <span style={{ color: '#38BDF8' }}>Pelayanan || Rumah Sakit</span><br />
          <span style={{ color: '#38BDF8' }}>Pelayanan || Puskesmas</span><br />
          <span style={{ color: '#4ADE80' }}>Jumlah / Total</span>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', fontSize: 9.8, color: '#5C3E00' }}>
          <b>Otomasi Colspan:</b> Backend PHP otomatis menyatukan induk yang sama menjadi satu header atas dengan lebar kolom yang pas.
        </div>
      </div>

      {/* Role & Units */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Peran Sel Kolom & Satuan
        </div>
        
        <div style={{ fontSize: 10.5, marginBottom: 10 }}>
          <b>4 Peran Sel Kolom (Cell Role):</b>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 4, marginTop: 4 }}>
            <span style={{ background: '#E0F2FE', color: '#0369A1', padding: '2px 6px', borderRadius: 3, textAlign: 'center', fontSize: 9.5, fontWeight: 700 }}>Data (Nilai)</span>
            <span style={{ background: '#FEF3C7', color: '#92400E', padding: '2px 6px', borderRadius: 3, textAlign: 'center', fontSize: 9.5, fontWeight: 700 }}>Subtotal</span>
            <span style={{ background: '#FEE2E2', color: '#B91C1C', padding: '2px 6px', borderRadius: 3, textAlign: 'center', fontSize: 9.5, fontWeight: 700 }}>Total Akhir</span>
            <span style={{ background: '#F3F4F6', color: '#374151', padding: '2px 6px', borderRadius: 3, textAlign: 'center', fontSize: 9.5, fontWeight: 700 }}>Keterangan</span>
          </div>
        </div>

        <div style={{ borderTop: `1px solid ${BORDER_LIGHT}`, paddingTop: 8, fontSize: 10.5 }}>
          <b>Pilihan Satuan / Unit Dwibahasa:</b>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4, marginTop: 4 }}>
            <span style={{ background: '#F1F5F9', padding: '2px 6px', borderRadius: 3, fontSize: 9.5 }}>Jiwa / Persons</span>
            <span style={{ background: '#F1F5F9', padding: '2px 6px', borderRadius: 3, fontSize: 9.5 }}>Ton</span>
            <span style={{ background: '#F1F5F9', padding: '2px 6px', borderRadius: 3, fontSize: 9.5 }}>Hektar</span>
            <span style={{ background: '#F1F5F9', padding: '2px 6px', borderRadius: 3, fontSize: 9.5 }}>Persen (%)</span>
          </div>
        </div>

        <div style={{ background: '#FFF4EC', borderLeft: `3px solid ${BPS_ORANGE}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 10, fontSize: 9.8, color: '#8C3203' }}>
          <b>Baris Nomor Kolom:</b> Sistem otomatis menyisipkan nomor peach <span style={{ fontWeight: 'bold' }}>(1), (2), (3)...</span> di bawah header.
        </div>
      </div>
    </div>

    {/* Thead Multi-Level Demo */}
    <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 10 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
        Hasil Render Thead Oranye BPS dengan Hierarki Bertingkat:
      </div>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 9.5, fontFamily: 'Arial, sans-serif' }}>
        <thead>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'center', fontWeight: 'bold' }}>
            <th rowSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px', width: '28%' }}>Kabupaten/Kota</th>
            <th colSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px' }}>Distribusi Farmasi</th>
            <th colSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px' }}>Pelayanan Medis</th>
            <th rowSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px' }}>Jumlah</th>
          </tr>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'center', fontWeight: 'bold' }}>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>PBF</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>Gudang</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>RS</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>Puskesmas</th>
          </tr>
          <tr style={{ background: BPS_PEACH, color: '#000000', fontWeight: 'bold', fontSize: 9, textAlign: 'center' }}>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(1)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(2)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(3)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(4)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(5)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(6)</th>
          </tr>
        </thead>
      </table>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 05: BAB 3: SETUP DIMENSI PERIODE
// ----------------------------------------------------------------------------
const Slide05DimensiPeriode: Page = () => (
  <A4BookletFrame chapterTag="BAB 03" chapterTitle="Setup Dimensi Periode" pageNumber={5}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        MENU: admin/master_dimensi
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Dimensi Waktu & Posisi Penempatan Header Periode
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* Frequency options */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          4 Jenis Frekuensi Waktu
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10 }}>
          <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: 6, borderRadius: 4 }}>
            <b>A. Tahunan (Annual):</b> Standar DDA (misal: <span style={{ fontFamily: 'monospace' }}>2022, 2023, 2024</span>).
          </div>
          <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: 6, borderRadius: 4 }}>
            <b>B. Semesteran (Bi-Annual):</b> <span style={{ fontFamily: 'monospace' }}>Semester I, Semester II</span>.
          </div>
          <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: 6, borderRadius: 4 }}>
            <b>C. Triwulanan (Quarterly):</b> <span style={{ fontFamily: 'monospace' }}>Triwulan I, II, III, IV</span>.
          </div>
          <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: 6, borderRadius: 4 }}>
            <b>D. Bulanan (Monthly):</b> <span style={{ fontFamily: 'monospace' }}>Januari s/d Desember</span>.
          </div>
        </div>
      </div>

      {/* Header position options */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Posisi Header Periode
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8, fontSize: 10 }}>
          <div style={{ border: `2px solid ${NAVY_PRIMARY}`, background: '#F0FDF4', padding: 8, borderRadius: 6 }}>
            <b style={{ color: NAVY_PRIMARY }}>Opsi 1: Periode di Atas Kolom (Rekomendasi)</b>
            <div style={{ color: TEXT_MUTED, marginTop: 2 }}>
              Tahun menjadi header atas pembanding (misal: 2023 vs 2024) lalu karakteristik di bawahnya.
            </div>
          </div>
          <div style={{ border: `1px solid ${BORDER_LIGHT}`, background: '#F8FAFC', padding: 8, borderRadius: 6 }}>
            <b>Opsi 2: Kolom di Atas Periode</b>
            <div style={{ color: TEXT_MUTED, marginTop: 2 }}>
              Karakteristik menjadi header atas, dan tahun menjadi rincian di bawah setiap kolom.
            </div>
          </div>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 8, fontSize: 9.8, color: '#5C3E00' }}>
          <b>Switcher Tahun di Web:</b> Pengunjung dapat mengganti tahun data secara instan lewat tombol filter di atas tabel portal.
        </div>
      </div>
    </div>

    {/* Header Demo */}
    <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 10 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
        Simulasi Header: Periode Tahun di Atas Kolom Karakteristik
      </div>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 9.5, fontFamily: 'Arial, sans-serif' }}>
        <thead>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'center', fontWeight: 'bold' }}>
            <th rowSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px', width: '32%' }}>Kabupaten/Kota</th>
            <th colSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px' }}>2023</th>
            <th colSpan={2} style={{ border: '1px solid #FFFFFF', padding: '4px' }}>2024</th>
          </tr>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'center', fontWeight: 'bold' }}>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>Laki-Laki</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>Perempuan</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>Laki-Laki</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '3px' }}>Perempuan</th>
          </tr>
          <tr style={{ background: BPS_PEACH, color: '#000000', fontWeight: 'bold', fontSize: 9, textAlign: 'center' }}>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(1)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(2)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(3)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(4)</th>
            <th style={{ border: '1px solid #FFFFFF', padding: '2px' }}>(5)</th>
          </tr>
        </thead>
      </table>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 06: BAB 4: PERAKITAN GENERATOR MATRIKS
// ----------------------------------------------------------------------------
const Slide06GeneratorMatriks: Page = () => (
  <A4BookletFrame chapterTag="BAB 04" chapterTitle="Generator Matriks" pageNumber={6}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        MENU: admin/master_tabel &rarr; f_generator_tabel
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Perakitan Matriks Tabel & Evaluasi Live Preview
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* 4 Steps generator */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Alur Generator Tabel
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10 }}>
          <div><b>1. Buka Master Tabel:</b> Klik aksi titik tiga $\rightarrow$ <b>Rakit Tabel Dinamis</b>.</div>
          <div><b>2. Pilih 3 Dimensi:</b> Karakteristik (kolom), Baris (wilayah), dan Periode (tahun).</div>
          <div>
            <b>3. Tentukan Desimal BPS:</b><br />
            • <span style={{ fontFamily: 'monospace' }}>0</span>: Bulat murni (<span style={{ fontFamily: 'monospace' }}>1.500</span>)<br />
            • <span style={{ fontFamily: 'monospace' }}>1</span>: 1 desimal (<span style={{ fontFamily: 'monospace' }}>12,5</span>)<br />
            • <span style={{ fontFamily: 'monospace' }}>2</span>: 2 desimal standar BPS (<span style={{ fontFamily: 'monospace' }}>1.007,98</span>)
          </div>
          <div><b>4. Input Metadata Resmi:</b> Isi Sumber Data & Catatan Kaki dwibahasa.</div>
        </div>
      </div>

      {/* Live Preview & Save */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Evaluasi Live Matrix Preview
        </div>
        <p style={{ fontSize: 10.5, color: TEXT_MUTED, lineHeight: 1.4, marginBottom: 8 }}>
          Panel sisi kanan menampilkan bentuk tabel secara realtime sebelum disimpan ke database:
        </p>

        <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: 8, borderRadius: 6, fontSize: 9.8, lineHeight: 1.45, color: '#334155' }}>
          • Periksa apakah susunan kolom bertingkat sudah pas?<br />
          • Periksa apakah banner wilayah (Kabupaten/Kota) sudah terarsir?<br />
          • Periksa apakah baris grand total sudah berwarna oranye di bawah?
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 10, fontSize: 9.8, color: '#5C3E00' }}>
          <b>Klik Simpan:</b> Status tabel otomatis aktif menjadi badge biru <span style={{ background: '#DBEAFE', color: '#1E40AF', padding: '1px 5px', borderRadius: 3, fontWeight: 700 }}>TABEL DINAMIS</span>.
        </div>
      </div>
    </div>

    {/* Saved Status Banner */}
    <div style={{ background: NAVY_DARK, color: '#FFFFFF', borderRadius: 8, padding: 12 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 }}>
        <span style={{ fontWeight: 700, fontSize: 11.5, color: YELLOW_GOLD }}>Konfigurasi Matriks Siap Digunakan</span>
        <span style={{ background: '#10B981', color: '#FFFFFF', fontSize: 10, fontWeight: 700, padding: '2px 8px', borderRadius: 4 }}>STATUS: AKTIF</span>
      </div>
      <div style={{ fontSize: 10.5, color: '#CBD5E1' }}>
        Tabel 1.1.1 terhubung ke Dimensi Baris 35 Kab/Kota, Kolom Jenis Kelamin, dan Periode 2024. Siap diisi data nilai.
      </div>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 07: BAB 5: METODE PENGISIAN A (INJEKSI CEPAT PDF)
// ----------------------------------------------------------------------------
const Slide07InjeksiPdf: Page = () => (
  <A4BookletFrame chapterTag="BAB 05" chapterTitle="Metode A: Injeksi PDF" pageNumber={7}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        MENU: admin/matching_pdf
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Pengisian Cepat Otomatis dari Buku PDF Master
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* 4 steps injeksi */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Alur Injeksi Cepat 1 Klik
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10 }}>
          <div><b>1. Buka Matching PDF:</b> Masuk menu <span style={{ fontFamily: 'monospace' }}>admin/matching_pdf</span>.</div>
          <div><b>2. Cari Nomor Tabel:</b> Temukan tabel (contoh: <span style={{ fontFamily: 'monospace' }}>Tabel 1.1.1</span>).</div>
          <div><b>3. Klik Tombol Kuning "Isi / Detail":</b> Terletak di kolom aksi tabel.</div>
          <div><b>4. Engine Otomatis Menyuntik Data:</b> Sistem membaca PDF dan memetakan koordinat sel ke database.</div>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 10, fontSize: 9.8, color: '#5C3E00' }}>
          <b>Hasil Instan:</b> Hanya butuh 1-2 detik, status tabel seketika berubah hijau: <span style={{ background: '#DCFCE7', color: '#166534', padding: '1px 5px', borderRadius: 3, fontWeight: 700 }}>100% Cocok (Data Sesuai)</span>.
        </div>
      </div>

      {/* Keamanan & Pembersihan */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Kelebihan & Kecepatan
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 9.8 }}>
          <div style={{ background: '#F8FAFC', borderLeft: '3px solid #10B981', padding: '5px 8px', borderRadius: 3 }}>
            <b>Hemat 95% Waktu:</b> Mengisi 35 kabupaten/kota secara instan tanpa perlu mengetik manual satu demi satu.
          </div>
          <div style={{ background: '#F8FAFC', borderLeft: `3px solid ${BPS_ORANGE}`, padding: '5px 8px', borderRadius: 3 }}>
            <b>Pembersihan Format Angka:</b> Spasi pemisah ribuan BPS dibersihkan otomatis (<span style={{ fontFamily: 'monospace' }}>1 180 811</span> $\rightarrow$ <span style={{ fontFamily: 'monospace' }}>1180811</span>).
          </div>
          <div style={{ background: '#F8FAFC', borderLeft: `3px solid ${NAVY_PRIMARY}`, padding: '5px 8px', borderRadius: 3 }}>
            <b>Audit Jejak Koordinat:</b> Koordinat halaman PDF tersimpan rapi untuk verifikasi pembuktian data.
          </div>
        </div>
      </div>
    </div>

    {/* UI Simulation */}
    <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 10 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
        Simulasi Daftar Tabel di Menu Matching PDF:
      </div>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 9.5, fontFamily: 'Arial, sans-serif' }}>
        <thead>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'center' }}>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '15%' }}>No Tabel</th>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '45%' }}>Judul Publikasi</th>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '22%' }}>Status</th>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '18%' }}>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr style={{ background: BPS_EVEN }}>
            <td style={{ padding: '4px', border: '1px solid #FFFFFF', textAlign: 'center', fontWeight: 'bold' }}>1.1.1</td>
            <td style={{ padding: '4px', border: '1px solid #FFFFFF' }}>Jumlah Penduduk Menurut Kab/Kota di Jawa Tengah</td>
            <td style={{ padding: '4px', border: '1px solid #FFFFFF', textAlign: 'center' }}>
              <span style={{ background: '#DCFCE7', color: '#166534', padding: '2px 8px', borderRadius: 50, fontWeight: 'bold', fontSize: 9 }}>
                ● 100% Cocok
              </span>
            </td>
            <td style={{ padding: '4px', border: '1px solid #FFFFFF', textAlign: 'center' }}>
              <button style={{ background: YELLOW_GOLD, color: NAVY_DARK, border: 'none', fontWeight: 'bold', padding: '3px 8px', borderRadius: 3, fontSize: 9.5 }}>
                ⚡ Isi / Detail
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 08: BAB 6: METODE PENGISIAN B (TEMPLATE EXCEL OPD)
// ----------------------------------------------------------------------------
const Slide08TemplateExcel: Page = () => (
  <A4BookletFrame chapterTag="BAB 06" chapterTitle="Metode B: Template Excel" pageNumber={8}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        DISTRIBUSI DATA OPD WALIDATA
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Alur Distribusi Template Excel & Upload Berproteksi
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* Excel workflow */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          SOP Pengumpulan Data Dinas
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10 }}>
          <div><b>1. Unduh Blanko Berproteksi:</b> Klik <b>Unduh Template Excel</b>. Berkas berekstensi <span style={{ fontFamily: 'monospace' }}>.xlsx</span> dengan metadata tersembunyi.</div>
          <div><b>2. Distribusi ke Dinas:</b> Kirim ke instansi luar. Header dan nama wilayah terkunci aman dari perubahan struktur.</div>
          <div><b>3. OPD Mengisi Sel Putih:</b> Hanya sel angka putih yang dapat diketik oleh dinas.</div>
          <div><b>4. Upload Kembali via Web:</b> Admin menerima berkas $\rightarrow$ Klik tombol hijau <b>UPLOAD EXCEL</b>.</div>
        </div>
      </div>

      {/* 3 Input Rules */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          3 Aturan Pengisian Dinas (Wajib)
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 9.8 }}>
          <div style={{ background: '#F8FAFC', borderLeft: '3px solid #10B981', padding: '5px 8px', borderRadius: 3 }}>
            <b>1. Angka Murni:</b> Ketik angka murni tanpa satuan. Contoh: <span style={{ fontFamily: 'monospace' }}>1500</span> atau <span style={{ fontFamily: 'monospace' }}>12.5</span>. Dilarang mengetik "1.500 jiwa".
          </div>
          <div style={{ background: '#F8FAFC', borderLeft: `3px solid ${BPS_PEACH}`, padding: '5px 8px', borderRadius: 3 }}>
            <b>2. Data Nihil / Nol:</b> Ketik tanda strip (<span style={{ fontFamily: 'monospace' }}>-</span>) atau angka <span style={{ fontFamily: 'monospace' }}>0</span>.
          </div>
          <div style={{ background: '#F8FAFC', borderLeft: '3px solid #EF4444', padding: '5px 8px', borderRadius: 3 }}>
            <b>3. Data Belum Tersedia:</b> Ketik titik-titik (<span style={{ fontFamily: 'monospace' }}>...</span>) atau kosongkan sel.
          </div>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 8, fontSize: 9.8, color: '#5C3E00' }}>
          <b>Engine Murni PHP:</b> Proses unggah berjalan murni pada PHP tanpa memerlukan library pihak ketiga atau python tambahan.
        </div>
      </div>
    </div>

    {/* Toolbar Action Banner */}
    <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
      <div>
        <div style={{ fontWeight: 700, fontSize: 11.5, color: NAVY_PRIMARY }}>Tombol Toolbar Excel di Halaman Web:</div>
        <div style={{ fontSize: 10, color: TEXT_MUTED }}>Terletak di sudut kanan atas viewer tabel dinamis admin.</div>
      </div>
      <div style={{ display: 'flex', gap: 8 }}>
        <span style={{ background: 'linear-gradient(135deg, #059669, #10B981)', color: '#FFFFFF', borderRadius: 50, padding: '4px 12px', fontSize: 10, fontWeight: 700 }}>
          📁 UPLOAD EXCEL
        </span>
        <span style={{ background: BPS_ORANGE, color: '#FFFFFF', borderRadius: 50, padding: '4px 12px', fontSize: 10, fontWeight: 700 }}>
          📥 EXPORT EXCEL
        </span>
      </div>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 09: BAB 7: METODE PENGISIAN C (INLINE EDITING WEB)
// ----------------------------------------------------------------------------
const Slide09InlineEdit: Page = () => (
  <A4BookletFrame chapterTag="BAB 07" chapterTitle="Metode C: Inline Edit Web" pageNumber={9}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        ADMIN VIEWER: admin/view_tabel_dinamis/{id}
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Revisi Langsung Nilai Sel di Browser (Inline AJAX)
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* 4 Steps Inline */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          4 Langkah Mudah Edit Langsung
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10 }}>
          <div><b>1. Buka Halaman Tabel:</b> Akses <span style={{ fontFamily: 'monospace' }}>admin/view_tabel_dinamis</span>.</div>
          <div><b>2. Double Click Sel Angka:</b> Klik dua kali tepat pada angka yang ingin dikoreksi.</div>
          <div><b>3. Sel Menjadi Input:</b> Ketik angka baru pada kotak input teks yang muncul.</div>
          <div><b>4. Tekan Enter untuk Simpan:</b> Nilai seketika tersimpan via request AJAX otomatis.</div>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 10, fontSize: 9.8, color: '#5C3E00' }}>
          <b>Sangat Praktis:</b> Menghemat waktu tanpa perlu unduh-unggah ulang file Excel hanya untuk memperbaiki 1 sel angka yang keliru.
        </div>
      </div>

      {/* Smart features */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Fitur Cerdas Inline Editor
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 9.8 }}>
          <div style={{ background: '#F0FDF4', border: '1px solid #BBF7D0', padding: '6px 8px', borderRadius: 4 }}>
            <b>Auto Re-Formatting BPS:</b> Ketik <span style={{ fontFamily: 'monospace' }}>1250000</span>, otomatis diformat menjadi <span style={{ fontFamily: 'monospace' }}>1 250 000</span> dengan spasi ribuan.
          </div>
          <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: '6px 8px', borderRadius: 4 }}>
            <b>Indikator Kedip Hijau:</b> Sel berkedip lembut sesaat setelah Enter ditekan sebagai konfirmasi penyimpanan sukses.
          </div>
          <div style={{ background: '#FFF7ED', border: '1px solid #FED7AA', padding: '6px 8px', borderRadius: 4 }}>
            <b>Proteksi Kategori & Total:</b> Baris banner dan header terkunci dari aksi klik ganda untuk mencegah salah edit struktur.
          </div>
        </div>
      </div>
    </div>

    {/* Active Edit Demo */}
    <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 10 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
        Simulasi Tampilan Sel yang Sedang Aktif Mode Edit Langsung:
      </div>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 9.5, fontFamily: 'Arial, sans-serif' }}>
        <thead>
          <tr style={{ background: BPS_ORANGE, color: '#FFFFFF', textAlign: 'center' }}>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '40%' }}>Kabupaten/Kota</th>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '30%' }}>2023</th>
            <th style={{ padding: '4px', border: '1px solid #FFFFFF', width: '30%' }}>2024</th>
          </tr>
        </thead>
        <tbody>
          <tr style={{ background: BPS_ODD }}>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>01. Cilacap</td>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF', textAlign: 'right' }}>1 893 256</td>
            <td style={{ background: '#FFFBE8', padding: '2px', border: '1px solid #FFFFFF', textAlign: 'right' }}>
              <input
                type="text"
                readOnly
                value="1905400"
                style={{ width: '90%', padding: '3px 4px', fontFamily: 'Arial', fontWeight: 'bold', fontSize: 9.5, textAlign: 'right', border: `2px solid ${BPS_ORANGE}`, borderRadius: 3, outline: 'none' }}
              />
            </td>
          </tr>
          <tr style={{ background: BPS_EVEN }}>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF' }}>02. Banyumas</td>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF', textAlign: 'right' }}>1 789 421</td>
            <td style={{ padding: '4px 6px', border: '1px solid #FFFFFF', textAlign: 'right' }}>1 802 110</td>
          </tr>
        </tbody>
      </table>
      <div style={{ fontSize: 9, color: TEXT_MUTED, marginTop: 4 }}>
        * Kotak oranye di atas adalah contoh tampilan sel Cilacap 2024 saat operator sedang mengetik revisi angka.
      </div>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 10: BAB 8: PUBLIKASI RESMI PORTAL PUBLIK (REPLIKA 100% IDENTIK)
// ----------------------------------------------------------------------------
const Slide10PublikasiPortal: Page = () => (
  <A4BookletFrame chapterTag="BAB 08" chapterTitle="Publikasi Portal Resmi" pageNumber={10}>
    <div style={{ marginBottom: 10 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        HALAMAN RESMI: tabel_dinamis/{id}
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Tampilan Publikasi Resmi & Ekspor Multi-Format
      </h2>
    </div>

    <div style={{ display: 'flex', gap: 8, marginBottom: 8, fontSize: 10 }}>
      <span style={{ background: '#E0F2FE', color: '#0369A1', padding: '3px 8px', borderRadius: 4, fontWeight: 600 }}>
        🌐 Akses Publik
      </span>
      <span style={{ background: '#FEF3C7', color: '#92400E', padding: '3px 8px', borderRadius: 4, fontWeight: 600 }}>
        📅 Filter Runtun Waktu
      </span>
      <span style={{ background: '#DCFCE7', color: '#166534', padding: '3px 8px', borderRadius: 4, fontWeight: 600 }}>
        💾 Ekspor XLSX, CSV & Print
      </span>
    </div>

    {/* Full Identic Table Replica */}
    <IdenticBpsTableMockup />
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// HALAMAN 11: BAB 9: RINGKASAN OPERASIONAL & FAQ
// ----------------------------------------------------------------------------
const Slide11RingkasanFaq: Page = () => (
  <A4BookletFrame chapterTag="BAB 09" chapterTitle="Ringkasan & Notasi BPS" pageNumber={11}>
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontSize: 10, fontWeight: 800, color: BPS_ORANGE, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        STANDARISASI STATISTIK
      </div>
      <h2 style={{ fontSize: 19, fontWeight: 800, color: NAVY_PRIMARY, letterSpacing: '-0.02em', marginTop: 2 }}>
        Matriks Keputusan & Notasi Resmi Publikasi BPS
      </h2>
    </div>

    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
      {/* Decision matrix */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Kapan Memilih Metode A, B, atau C?
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 10 }}>
          <div style={{ background: '#F0FDF4', border: '1px solid #BBF7D0', padding: 6, borderRadius: 4 }}>
            <b style={{ color: '#15803D' }}>Metode A: Injeksi PDF Master</b>
            <div style={{ color: TEXT_MUTED }}>Buku PDF DDA sudah terbit di server. 1 klik menyuntikkan seluruh angka.</div>
          </div>
          <div style={{ background: '#F8FAFC', border: `1px solid ${BORDER_LIGHT}`, padding: 6, borderRadius: 4 }}>
            <b style={{ color: NAVY_PRIMARY }}>Metode B: Distribusi Excel OPD</b>
            <div style={{ color: TEXT_MUTED }}>Data bersumber dari dinas luar (Dinkes, Disdik, Dishub). Header terkunci.</div>
          </div>
          <div style={{ background: '#FFFBE8', border: '1px solid #FDE68A', padding: 6, borderRadius: 4 }}>
            <b style={{ color: '#92400E' }}>Metode C: Inline Edit Web</b>
            <div style={{ color: TEXT_MUTED }}>Koreksi darurat 1 atau 2 sel angka salah ketik saat proses final proofreading.</div>
          </div>
        </div>
      </div>

      {/* BPS Symbols */}
      <div style={{ background: '#FFFFFF', border: `1px solid ${BORDER_LIGHT}`, borderRadius: 8, padding: 12 }}>
        <div style={{ fontSize: 11.5, fontWeight: 700, color: NAVY_PRIMARY, marginBottom: 6 }}>
          Arti Simbol & Notasi Resmi BPS
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 5, fontSize: 10 }}>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <span style={{ fontFamily: 'monospace', background: '#F1F5F9', padding: '1px 5px', borderRadius: 3, width: 28, textAlign: 'center' }}>...</span>
            <span><b>Data Belum Tersedia</b> (Belum ada rilis)</span>
          </div>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <span style={{ fontFamily: 'monospace', background: '#F1F5F9', padding: '1px 5px', borderRadius: 3, width: 28, textAlign: 'center' }}>–</span>
            <span><b>Nihil / Nol</b> (Tidak ada kejadian)</span>
          </div>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <span style={{ fontFamily: 'monospace', background: '#F1F5F9', padding: '1px 5px', borderRadius: 3, width: 28, textAlign: 'center' }}>*</span>
            <span><b>Angka Sementara</b> (Masih bisa berubah)</span>
          </div>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <span style={{ fontFamily: 'monospace', background: '#F1F5F9', padding: '1px 5px', borderRadius: 3, width: 28, textAlign: 'center' }}>r</span>
            <span><b>Angka Revisi</b> (Perbaikan terbitan lalu)</span>
          </div>
        </div>

        <div style={{ background: YELLOW_LIGHT, borderLeft: `3px solid ${YELLOW_GOLD}`, padding: 8, borderRadius: '0 6px 6px 0', marginTop: 8, fontSize: 9.8, color: '#5C3E00' }}>
          <b>Pemisah Ribuan & Desimal:</b> Publikasi BPS menggunakan <b>spasi tipis</b> untuk ribuan (<span style={{ fontFamily: 'monospace' }}>1 500 000</span>) dan <b>koma</b> untuk desimal (<span style={{ fontFamily: 'monospace' }}>12,50</span>).
        </div>
      </div>
    </div>

    {/* Institutional Helpdesk */}
    <div style={{ background: NAVY_DARK, color: '#FFFFFF', borderRadius: 8, padding: 12, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
      <div>
        <div style={{ fontWeight: 800, fontSize: 12, color: YELLOW_GOLD }}>Bantuan Teknis & Konsultasi Sistem DDA Online</div>
        <div style={{ fontSize: 10.5, color: '#CBD5E1' }}>Tim Pengolahan Data & Statistik Sosial • BPS Provinsi Jawa Tengah</div>
      </div>
      <span style={{ background: BPS_ORANGE, color: '#FFFFFF', padding: '4px 12px', borderRadius: 50, fontWeight: 700, fontSize: 10 }}>
        DDA Online v2.4
      </span>
    </div>
  </A4BookletFrame>
);

// ----------------------------------------------------------------------------
// METADATA & EXPORT DECK 11 HALAMAN
// ----------------------------------------------------------------------------
export const meta: SlideMeta = {
  title: 'Buku Panduan Sistem Tabel Dinamis (Canva A4 Edition)',
  theme: 'dark',
};

const pages: Page[] = [
  Slide01Cover,
  Slide02DaftarIsi,
  Slide03DimensiBaris,
  Slide04DimensiKolom,
  Slide05DimensiPeriode,
  Slide06GeneratorMatriks,
  Slide07InjeksiPdf,
  Slide08TemplateExcel,
  Slide09InlineEdit,
  Slide10PublikasiPortal,
  Slide11RingkasanFaq,
];

export default pages;