<template>
  <div>
    <SubmitBar
      :price="totalPrice"
      :disabled="isDisabled"
      button-text="结算"
      @submit="showPopup = true"
    >
      <div class="btn-card" @click="showPopup = true">
        <span class="badge">{{countNum}}</span>
        <van-icon name="shopping-cart-o" />
      </div>
    </SubmitBar>
    <PopupCart v-model="showPopup" />
  </div>
</template>

<script>
import SubmitBar from "@/components/SubmitBar";
import PopupCart from "./PopupCart";
export default {
  data() {
    return {
      showPopup: false
    };
  },
  computed: {
    isDisabled() {
      return (
        this.$store.state._store.cartList.every(({ checked }) => !checked) ||
        this.$store.state._store.isBtnDisabled
      );
    },
    totalPrice() {
      return this.$store.state._store.totalPrice;
    },
    countNum() {
      return this.$store.state._store.cartList.length;
    }
  },
  mounted() {
    this.$store.dispatch("getStoreCartList", {
      store_id: this.$route.params.id
    });
  },
  components: {
    SubmitBar,
    PopupCart
  }
};
</script>

<style scoped>
.btn-card {
  display: flex;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  border: 1px solid #ddd;
  border-radius: 50%;
  font-size: 18px;
  color: #666;
  position: relative;
  left: 15px;
}

.badge {
  position: absolute;
  right: -10px;
  top: -6px;
  line-height: 1;
  padding: 0.3em;
  font-size: 12px;
  -webkit-transform: scale(0.9);
  transform: scale(0.9);
  border-radius: 0.8em;
  background: red;
  color: #fff;
  z-index: 9;
  min-width: 1.5em;
  max-width: 28px;
  white-space: nowrap;
  overflow: hidden;
  display: block;
  text-align: center;
}
</style>