const pageContent = {
  topbar: {
    location:
      "CDCI Global, Inc. 25309 Loganshire Terrace, Unit 104 Chantilly, VA 20152",
    email: "info@cdciconsulting.com",
    socialLinks: {
      facebook: "#",
      twitter: "#",
      instagram: "#",
      linkedin: "#",
    },
    languages: ["English", "Bangla", "French", "Spanish", "Arabic"],
  },
  navbar: {
    brand: "CDCI Global",
    links: [
      { name: "Home", href: "index.html" },
      {
        name: "About Us",
        dropdown: [
          { name: "About CDCI", href: "about.html" },
          { name: "Commitment to Quality", href: "commitment-to-quality.html" },
          { name: "Our Team", href: "team.html" },
        ],
      },
      { name: "Services", href: "services.html" },
      { name: "Projects", href: "projects.html" },
      { name: "News", href: "news.html" },
      { name: "Contact", href: "contact.html" },
    ],
    callToAction: {
      text: "Work with Us",
      href: "#",
    },
    phone: {
      text: "Free: + 0123 456 7890",
      href: "tel:+01234567890",
    },
  },
  vision: {
    text: "Our Vision is to be a Global Leader in delivering solutions to complex challenges while empowering people to reach their full potential.",
  },
  carousel: [
    {
      title: "Governance and Accountability",
      description:
        "CDCI understands the extensiveness of the Governance and Accountability sector as it cuts across both the public and private sectors.",
      image: "img/african-business-male-people-shaking-hands.jpg",
    },
    {
      title: "Water, Sanitation and Health (WASH)",
      description:
        "Development and implementation of community-based WASH management models – this entails feasibility studies and the development of appropriate management models.",
      image: "img/water and sanitation.png",
    },
    {
      title: "Trade and Logistics",
      description:
        "CDCI understands the extensiveness of the Governance and Accountability sector as it cuts across both the public and private sectors.",
      image: "img/trade-logistics.jpg",
    },
    {
      title: "Agriculture and Food Security",
      description:
        "Food security worldwide is increasingly causing concern more so in developing countries with the most vulnerable being the smallholder farmers.",
      image: "img/agriculture.jpg",
    },
  ],
  features: [
    {
      title: "Governance and Accountability",
      description:
        "Governance as Process, Public Institution Governance, Corporate Governance, Governance Analytical Framework, Public Financial Management, Organizational Capacity Building, Policy Reform...",
      icon: "fas fa-gavel",
      link: "services.html#governance",
    },
    {
      title: "Agriculture and Food Security",
      description:
        "Food security worldwide is increasingly causing concern, more so in developing countries, with the most vulnerable being smallholder farmers. The main concerns are insufficient food production caused by supply chain...",
      icon: "fas fa-seedling",
      link: "services.html#agriculture",
    },
    {
      title: "Water, Sanitation and Health (WASH)",
      description:
        "Non-Revenue Water (NRW) Management, Revenue/Billing/Tariffs, Public Private Partnerships, Community water management systems...",
      icon: "fas fa-faucet",
      link: "services.html#wash",
    },
    {
      title: "Trade and Logistics",
      description:
        "Free trade areas (FTA), Efficient clearing through Single Window systems, Transport corridors with no barriers, Market linkages and systems...",
      icon: "fas fa-truck",
      link: "services.html#trade",
    },
  ],
};
