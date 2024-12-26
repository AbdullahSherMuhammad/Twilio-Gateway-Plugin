<style>
    .quick-call-container {
        background-color: white;
        width: 320px;
        padding: 20px;
        margin: 30px auto;
        height: auto;
        text-align: center;
        border: 1px solid #ccc;
    }

    .quick-call-row {
        margin: 0 auto;
        clear: both;
        text-align: center;
        font-family: 'Exo', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 17px;
        margin-top: 10px;
    }

    .center-sub-text {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    .quick-call-digit {
        float: left;
        padding: 10px 40px;
        width: 30px;
        font-size: 2rem;
        cursor: pointer;
        transition: .3s all ease-in-out;
        border: 1px solid #ccc !important;
    }

    .quick-call-digit .sub {
        font-size: 0.8rem;
        color: grey;
    }

    .quick-call-output {
        font-family: "Exo", sans-serif;
        font-size: 2rem;
        height: 60px;
        font-weight: bold;
        color: #000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #ccc;
        padding: 0 10px;
        position: relative;
        margin-top: 10px;
    }

    #number-display {
        flex: 1;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
    }

    .delete-icon {
        cursor: pointer;
        color: black;
        font-size: 1.5rem;
        position: absolute;
        right: 10px;
        transition: .3s all ease-in-out;
    }

    .delete-icon:hover {
        color: red;
    }

    .quick-call-btn {
        display: inline-block;
        background-color: #28A745;
        margin: 10px;
        color: white;
        cursor: pointer;
        height: 50px;
        width: 150px;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: 0.3s all ease-in-out;
    }

    .quick-call-botrow {
        margin: 0 auto;
        width: 280px;
        clear: both;
        text-align: center;
        font-family: 'Exo', sans-serif;
    }

    .quick-call-digit:hover {
        background-color: #e6e6e6;
    }

    .quick-call-digit:active {
        background-color: #e6e6e6;
    }

    .quick-call-btn:hover {
        background-color: #1a772f;
    }

    .quick-call-extra {
        float: left;
        padding: 10px 20px;
        margin: 10px;
        width: 30px;
        cursor: pointer;
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

<div class="quick-call-container">
    <div class="quick-call-output">
        <span id="conf-number-display"></span>
        <i class="fa-solid fa-delete-left delete-icon"></i>
    </div>
    <div class="center-sub-text">
        <div class="quick-call-row">
            <div class="quick-call-digit center-sub-text">1</div>
            <div class="quick-call-digit center-sub-text">2</div>
            <div class="quick-call-digit center-sub-text">3</div>
        </div>
        <div class="quick-call-row">
            <div class="quick-call-digit center-sub-text">4</div>
            <div class="quick-call-digit center-sub-text">5</div>
            <div class="quick-call-digit center-sub-text">6</div>
        </div>
        <div class="quick-call-row">
            <div class="quick-call-digit center-sub-text">7</div>
            <div class="quick-call-digit center-sub-text">8</div>
            <div class="quick-call-digit center-sub-text">9</div>
        </div>
        <div class="quick-call-row">
            <div class="quick-call-digit center-sub-text">+</div>
            <div class="quick-call-digit center-sub-text">0</div>
            <div class="quick-call-digit center-sub-text">#</div>
        </div>
    </div>
   
    <div class="quick-call-row">
        <div class="quick-call-btn" id="makeConfCallButton">
            <i class="fa-solid fa-phone"></i>
        </div>
    </div>
</div>