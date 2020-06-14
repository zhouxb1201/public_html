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
              <div class="card__price-group" v-if="total_count">
                <span class="card__price">共{{total_count}}节</span>
              </div>
              <div class="card__btn" v-if="showbtn">
                <van-button
                  type="danger"
                  size="small"
                  :to="id"
                >{{buttomText}}</van-button>
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
    total_count: [String, Number],
    buttomText: String,
    showbtn: {
      type: Boolean,
      default: true
    },
  },
  methods: {}
};
</script>

<style scoped>
.card-group-box {
  margin: 10px 0;
  border-radius: 0px;
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
  width: 60px;
  height: 60px;
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
  height: 60px;
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
  align-items: flex-end;
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
  color: #999;
  font-weight: 700;
}
.card__text{
  display: inline-block;
  color: #333;
  font-weight: 700;
}
.card__text label{
  color: #666;
}
.card__btn {
  float: right;
}
.card__btn >>> .van-button {
  border-radius: 4px;
}
</style>