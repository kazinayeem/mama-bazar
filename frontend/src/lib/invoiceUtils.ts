import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'

export const generateInvoiceFilename = (orderId: string): string => {
  const cleanId = orderId.replace(/[^a-zA-Z0-9-]/g, '')
  return `MamaBazar-Invoice-${cleanId}.pdf`
}

export const printInvoice = (elementId = 'invoice-content'): void => {
  const element = document.getElementById(elementId)
  if (!element) return

  const printWindow = window.open('', '_blank', 'width=800,height=900')
  if (!printWindow) return

  printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
  <title>Invoice</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    @page {
      size: A4;
      margin: 12mm;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', system-ui, sans-serif;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color-adjust: exact;
    }
  </style>
</head>
<body>
  ${element.outerHTML}
  <script>
    window.onload = function() {
      setTimeout(function() { window.print(); window.close(); }, 300);
    };
  </script>
</body>
</html>`)
  printWindow.document.close()
}

export const downloadInvoicePdf = async (elementId = 'invoice-content', filename?: string): Promise<void> => {
  const element = document.getElementById(elementId)
  if (!element) throw new Error('Invoice element not found')

  const targetFilename = filename || 'MamaBazar-Invoice.pdf'

  const originalWidth = element.style.width
  const originalMaxWidth = element.style.maxWidth
  element.style.width = '210mm'
  element.style.maxWidth = '210mm'

  try {
    const canvas = await html2canvas(element, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff',
      logging: false,
      width: element.scrollWidth,
      height: element.scrollHeight,
    })

    const imgWidth = 210
    const imgHeight = (canvas.height * imgWidth) / canvas.width

    const pdf = new jsPDF({
      orientation: imgHeight > 297 ? 'portrait' : 'portrait',
      unit: 'mm',
      format: 'a4',
    })

    const pageHeight = 297
    let heightLeft = imgHeight
    let position = 0

    pdf.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 0, position, imgWidth, imgHeight)
    heightLeft -= pageHeight

    while (heightLeft > 0) {
      position = heightLeft - imgHeight
      pdf.addPage()
      pdf.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 0, position, imgWidth, imgHeight)
      heightLeft -= pageHeight
    }

    pdf.save(targetFilename)
  } finally {
    element.style.width = originalWidth
    element.style.maxWidth = originalMaxWidth
  }
}
