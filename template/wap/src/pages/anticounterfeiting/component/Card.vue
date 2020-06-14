<template>
  <div class="card-group-box">
    <div class="van-cell">
      <div class="van-cell__value van-cell__value--alone">
        <div class="card">
          <div class="card__thumb">
            <img :src="image" :onerror="$ERRORPIC.noGoods" />
          </div>
          <div class="card__content">
            <div class="card__title">{{name ? name : ''}}</div>
            <div class="card__bottom">
              <div class="card__price-group">
                <span class="card__price">{{currency}}{{price}}</span>
              </div>
              <div class="card__btn" v-if="state">
                <van-button type="danger" size="small" @click="onGoods">点击购买</van-button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    id: [String, Number],
    image: String,
    name: String,
    price: [String, Number],
    state: [String, Number],
    currency: {
      type: String,
      default: "¥"
    }
  },
  methods: {
    onGoods() {
      if (this.id) {
        this.$router.push({
          name: "goods-detail",
          params: {
            goodsid: this.id
          }
        });
      }
    },
    onBatch() {
      let { id, image, name, price, state } = goods;
      this.$router.repalce({
        name: "anticounterfeiting-batch",
        params: {
          goods: goods
        }
      });
    }
  }
};
</script>

<style scoped>
.card-group-box {
  margin: 10px;
  border-radius: 10px;
  overflow: hidden;
  -webkit-box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
}
.card {
  position: relative;
  color: #323233;
  font-size: 12px;
  padding: 0;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  background-color: #ffffff;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
}
.card__thumb {
  position: relative;
  width: 50px;
  height: 50px;
  margin-right: 10px;
  -webkit-box-flex: 0;
  -ms-flex: none;
  flex: none;
  background: #f9f9f9;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-box-pack: center;
  -ms-flex-pack: center;
  justify-content: center;
}
.card__thumb img {
  border: 0;
  max-width: 100%;
  max-height: 100%;
}
.card__content {
  position: relative;
  -webkit-box-flex: 1;
  -ms-flex: 1;
  flex: 1;
  height: 50px;
  min-width: 0;
}
.card__title {
  line-height: 16px;
  max-height: 32px;
  height: 32px;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-all;
}
.card__bottom {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  line-height: 18px;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: justify;
  -ms-flex-pack: justify;
  justify-content: space-between;
}
.card__price-group {
  white-space: nowrap;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
}
.card__price {
  display: inline-block;
  color: #ff454e;
  font-weight: 700;
}
.card__btn {
  float: right;
}
.card__btn >>> .van-button {
  border-radius: 4px;
}
</style>