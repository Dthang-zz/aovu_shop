{{-- Inline so the styles survive the admin SPA swap, which only replaces the page body regions. --}}
<style>
    .return-meta { display: flex; align-items: flex-start; }
    .return-meta__icon { width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-right: .75rem; background: #eef4ff; color: #3b6fd8; }
    .return-meta__label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #8a94a6; margin-bottom: .1rem; }
    .return-meta__value { font-weight: 600; color: #1f2d3d; line-height: 1.3; }
    .return-section-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; margin-bottom: .75rem; }
    .return-product { border: 1px solid #e9ecef; border-radius: 10px; padding: .9rem 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .return-quote { border-left: 4px solid #adb5bd; background: #f8f9fa; border-radius: 0 8px 8px 0; padding: .75rem 1rem; color: #343a40; white-space: pre-line; }
    .return-reply { border-left: 4px solid #3b6fd8; background: #f2f6fd; border-radius: 0 8px 8px 0; padding: .75rem 1rem; white-space: pre-line; }
    .return-bank { border-radius: 12px; padding: 1.1rem 1.25rem; color: #fff; background: linear-gradient(135deg, #1f4e9c 0%, #3b6fd8 100%); }
    .return-bank__label { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; opacity: .75; }
    .return-bank__number { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: 1.35rem; letter-spacing: .12em; font-weight: 600; word-break: break-all; }
    .return-check { display: flex; align-items: flex-start; padding: .65rem 0; border-bottom: 1px dashed #e9ecef; }
    .return-check:last-child { border-bottom: 0; }
    .return-check__icon { width: 26px; flex-shrink: 0; font-size: 1.05rem; margin-top: .05rem; }
    .return-check__title { font-weight: 600; color: #1f2d3d; }
    .return-check__note { color: #6c757d; font-size: .875rem; margin-top: .15rem; white-space: pre-line; }
    .return-steps { list-style: none; padding: 0; margin: 0; }
    .return-steps li { position: relative; padding: 0 0 1rem 2rem; }
    .return-steps li:last-child { padding-bottom: 0; }
    .return-steps li::before { content: ''; position: absolute; left: .55rem; top: 1.3rem; bottom: 0; width: 2px; background: #e9ecef; }
    .return-steps li:last-child::before { display: none; }
    .return-steps li.is-done::before { background: #28a745; }
    .return-steps__dot { position: absolute; left: 0; top: .1rem; width: 1.2rem; height: 1.2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .6rem; background: #fff; border: 2px solid #ced4da; color: #fff; }
    .return-steps li.is-done .return-steps__dot { background: #28a745; border-color: #28a745; }
    .return-steps li.is-current .return-steps__dot { border-color: #3b6fd8; box-shadow: 0 0 0 4px rgba(59, 111, 216, .18); background: #3b6fd8; }
    .return-steps li.is-rejected .return-steps__dot { background: #dc3545; border-color: #dc3545; }
    .return-steps li.is-skipped .return-steps__label { text-decoration: line-through; color: #adb5bd; }
    .return-steps__label { font-weight: 600; color: #495057; }
    .return-steps li.is-pending .return-steps__label { color: #adb5bd; font-weight: 500; }
    .return-steps__time { font-size: .75rem; color: #8a94a6; }
</style>
