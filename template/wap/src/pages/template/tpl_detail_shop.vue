<template>
  <div :class="item.id" :style="viewStyle" v-if="isShow">
    <van-cell-group>
      <van-cell value-class="info-panel" :border="false">
        <div class="item-left">
          <img class="img" :src="item.params.logo" :onerror="$ERRORPIC.noShop" />
        </div>
        <div class="item-right">
          <div class="title">
            <div class="left-text" :style="{color:item.style.namecolor}">{{item.params.name}}</div>
            <div class="right-text">
              <van-button
                size="mini"
                round
                type="danger"
                :style="btnStyle"
                :to="'/goods/list?shop_id='+item.params.id"
              >全部商品</van-button>
              <van-button
                size="mini"
                round
                type="danger"
                :style="btnStyle"
                :to="'/shop/home/'+item.params.id"
              >进入店铺</van-button>
            </div>
          </div>
          <div class="text">
            <Star
              :value="item.params.comprehensive||0"
              :size="10"
              :color="item.style.starcolor"
              :voidColor="item.style.starcolor"
            />
          </div>
        </div>
      </van-cell>
      <van-cell value-class="score-panel" :border="false">
        <div class="item">
          <span class="fs-12" :style="{color:item.style.titlecolor}">描述</span>
          <span :style="{color:item.style.scorecolor}">{{item.params.desc}}</span>
        </div>
        <div class="item van-hairline--left">
          <span class="fs-12" :style="{color:item.style.titlecolor}">物流</span>
          <span :style="{color:item.style.scorecolor}">{{item.params.delivery}}</span>
        </div>
        <div class="item van-hairline--left">
          <span class="fs-12" :style="{color:item.style.titlecolor}">服务</span>
          <span :style="{color:item.style.scorecolor}">{{item.params.service}}</span>
        </div>
      </van-cell>
    </van-cell-group>
  </div>
</template>

<script>
import Star from "@/components/Star";
export default {
  name: "tpl_detail_shop",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    isShow() {
      return !!this.$store.state.config.addons.shop;
    },
    viewStyle() {
      return {
        marginTop: this.item.style.margintop + "px",
        marginBottom: this.item.style.marginbottom + "px"
      };
    },
    btnStyle() {
      return {
        background: this.item.style.btncolor,
        color: this.item.style.btntextcolor
      };
    }
  },
  components: {
    Star
  }
};
</script>

<style scoped>
.info-panel {
  display: flex;
}

.item-left {
  flex: 0.2;
  margin-right: 10px;
  position: relative;
}

.img {
  width: 85px;
  height: 48px;
  display: block;
  border-radius: 8px;
  background: #f8f8f8;
}

.item-right {
  flex: 1.8;
  flex-direction: column;
  overflow: hidden;
}

.title {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left-text {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.right-text {
  display: flex;
  align-items: center;
}

.text {
  display: flex;
  align-items: center;
}

.score-panel {
  display: flex;
  align-items: center;
  justify-content: center;
}

.score-panel .item {
  display: flex;
  flex-flow: column;
  align-items: center;
  flex: 1;
  line-height: 1.4;
}
</style>
