<template>
  <div>
    <SubmitBar
      :price="totalPrice"
      :disabled="isDisabled"
      :button-text="$route.params.type == 'purchase' ? '结算' : '提货'"
      :loading="isLoading"
      @submit="onSubmit"
    >
      <div slot="tip" class="text-center" v-if="isShowTip" v-html="$store.state.channel.message"/>
      <div class="btn-card" @click="showPopup = true">
        <span class="badge">{{countNum}}</span>
        <van-icon name="shopping-cart-o"/>
      </div>
      <div slot="label" v-if="$route.params.type !== 'purchase'">
        <span>已选：</span>
        <span class="bar-price">{{countNum}}</span>件商品
      </div>
    </SubmitBar>
    <PopupCart v-model="showPopup" :list="list"/>
  </div>
</template>
<script>
import SubmitBar from "@/components/SubmitBar";
import PopupCart from "./PopupCart";
import { isEmpty } from "@/utils/util";
export default {
  data() {
    return {
      showPopup: false,
      isLoading: false
    };
  },
  computed: {
    list() {
      return this.$store.state.channel.cartList;
    },
    totalPrice() {
      return parseFloat(this.$store.state.channel.total_money);
    },
    countNum() {
      let num = 0;
      if (!isEmpty(this.list)) {
        this.list.forEach(e => {
          num += parseFloat(e.num);
        });
      }
      return num;
    },
    isShowTip() {
      let isShow = false;
      if (this.$route.params.type == "purchase") {
        isShow =
          !this.$store.state.channel.isAchieveCondie &&
          this.$store.state.channel.message;
      }
      return isShow;
    },
    isDisabled() {
      let flag = true;
      if (this.$route.params.type == "purchase") {
        flag =
          !this.$store.state.channel.isAchieveCondie || isEmpty(this.list);
      } else {
        flag = isEmpty(this.list);
      }
      return flag;
    }
  },
  mounted() {
    this.getCartList();
  },
  methods: {
    getCartList() {
      this.$store.dispatch("getChannelCartList", {
        page_index: 1,
        page_size: 20,
        buy_type: this.$route.params.type
      });
    },
    onSubmit() {
      const type = this.$route.params.type;
      this.isLoading = true;
      this.$router.push({
        name: "channel-order-confirm",
        params: {
          type
        }
      });
    }
  },
  deactivated() {
    if (this.isLoading) {
      this.isLoading = false;
    }
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

.bar-price {
  color: #ff454e;
  font-weight: 800;
  padding: 0 2px;
}
</style>
