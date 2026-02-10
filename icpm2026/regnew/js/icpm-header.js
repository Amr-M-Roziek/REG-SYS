class ICPMHeader extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const logoSrc = this.getAttribute('logo-src') || '../images/icpm-logo.png';
    const homeUrl = this.getAttribute('home-url') || 'https://icpm.ae';
    
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          font-family: 'Jaldi', 'Helvetica Neue', Arial, sans-serif;
          background-color: #0b1c2c; /* Deep blue background from image */
          color: white;
          width: 100%;
          z-index: 1000;
          position: relative;
        }

        .header-container {
          display: flex;
          justify-content: space-between;
          align-items: center;
          max-width: 1200px;
          margin: 0 auto;
          padding: 10px 20px;
        }

        .logo {
          flex-shrink: 0;
        }

        .logo img {
          height: 50px;
          display: block;
        }

        .nav-toggle {
          display: none;
          background: none;
          border: none;
          color: white;
          font-size: 24px;
          cursor: pointer;
        }

        .nav-menu {
          display: flex;
          gap: 25px;
          list-style: none;
          margin: 0;
          padding: 0;
          align-items: center;
        }

        .nav-item {
          position: relative;
        }

        .nav-link {
          color: white;
          text-decoration: none;
          font-weight: 700;
          font-size: 56px;
          text-transform: uppercase;
          padding: 10px 0;
          transition: color 0.3s ease;
          letter-spacing: 0.5px;
        }

        .nav-link:hover, .nav-link:focus {
          color: #e91e63; /* Pinkish color from logo heart */
          outline: none;
        }

        /* Dropdown */
        .dropdown-content {
          display: none;
          position: absolute;
          top: 100%;
          left: 0;
          background-color: #0b1c2c;
          min-width: 160px;
          box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
          z-index: 1;
          padding: 10px 0;
        }

        .nav-item:hover .dropdown-content,
        .nav-item:focus-within .dropdown-content {
          display: block;
        }

        .dropdown-item {
          color: white;
          padding: 12px 16px;
          text-decoration: none;
          display: block;
          font-size: 13px;
          transition: background 0.3s;
        }

        .dropdown-item:hover {
          background-color: #1a2f42;
          color: #e91e63;
        }

        .chevron {
          font-size: 10px;
          margin-left: 5px;
          vertical-align: middle;
        }

        /* Responsive */
        @media screen and (max-width: 900px) {
          .nav-toggle {
            display: block;
          }

          .nav-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background-color: #0b1c2c;
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
          }

          .nav-menu.active {
            display: flex;
          }

          .nav-item {
            width: 100%;
            margin-bottom: 10px;
          }

          .dropdown-content {
            position: static;
            box-shadow: none;
            padding-left: 20px;
            background-color: #081521;
          }
          
          .nav-link {
            display: block;
            width: 100%;
          }
        }
      </style>

      <div class="header-container">
        <div class="logo">
          <a href="${homeUrl}" aria-label="ICPM Home">
            <img src="${logoSrc}" alt="ICPM - International Conference of Pharmacy & Medicine">
          </a>
        </div>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
          ☰
        </button>

        <ul class="nav-menu">
          <li class="nav-item"><a href="https://icpm.ae/" class="nav-link">Home</a></li>
        </ul>
      </div>
    `;

    // Event Listeners
    const toggleBtn = this.shadowRoot.querySelector('.nav-toggle');
    const navMenu = this.shadowRoot.querySelector('.nav-menu');

    toggleBtn.addEventListener('click', () => {
      const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
      toggleBtn.setAttribute('aria-expanded', !expanded);
      navMenu.classList.toggle('active');
    });
  }
}

customElements.define('icpm-header', ICPMHeader);
