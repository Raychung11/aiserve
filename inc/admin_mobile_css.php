<style>
/* Mobile-first admin enhancement */
.top-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.table-wrap{
    overflow:auto;
    -webkit-overflow-scrolling:touch;
}
.stack-mobile{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
.mobile-card-list{
    display:none;
    gap:14px;
}
.mobile-card{
    background:#fff;
    border:1px solid #e8defd;
    border-radius:18px;
    padding:16px;
    box-shadow:0 12px 30px rgba(109,40,217,.08);
}
.mobile-card .row{
    margin-bottom:8px;
    font-size:14px;
}
.mobile-card .label{
    color:#6f6785;
    font-weight:700;
    display:block;
    font-size:12px;
    margin-bottom:2px;
}

@media (max-width: 980px){
    .layout{
        grid-template-columns:1fr !important;
    }
    .sidebar{
        padding:16px !important;
    }
    .menu{
        grid-template-columns:repeat(2,1fr);
    }
    .main{
        padding:16px !important;
    }
    .top{
        flex-direction:column;
        align-items:flex-start !important;
    }
    .stats{
        grid-template-columns:repeat(2,1fr) !important;
    }
}

@media (max-width: 760px){
    .menu{
        grid-template-columns:1fr 1fr;
    }
    .stats{
        grid-template-columns:1fr !important;
    }
    .form-grid{
        grid-template-columns:1fr !important;
    }
    table.desktop-table{
        display:none;
    }
    .mobile-card-list{
        display:grid;
    }
}
</style>